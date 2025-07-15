<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service;

use OneMoveTwo\Offers\Api\OfferItemManagementInterface;
use OneMoveTwo\Offers\Api\OfferItemRepositoryInterface;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Api\OfferManagementInterface;
use OneMoveTwo\Offers\Api\Data\OfferItemInterface;
use OneMoveTwo\Offers\Api\Data\OfferItemInterfaceFactory;
use OneMoveTwo\Offers\Service\Validation\OfferItemValidator;
use OneMoveTwo\Offers\Service\Calculator\ItemCalculator;
use OneMoveTwo\Offers\Service\History\OfferHistoryManagementService;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

readonly class OfferItemManagementService implements OfferItemManagementInterface
{
    public function __construct(
        private OfferItemRepositoryInterface $offerItemRepository,
        private OfferRepositoryInterface     $offerRepository,
        private OfferManagementInterface     $offerManagement,
        private OfferItemInterfaceFactory    $itemFactory,
        private OfferItemValidator           $itemValidator,
        private ItemCalculator               $itemCalculator,
        private OfferHistoryManagementService          $historyManager,
        private ProductRepositoryInterface   $productRepository
    ) {
    }

    public function addItemToOffer(int $offerId, OfferItemInterface $item): OfferItemInterface
    {
        // Validate offer exists and is editable
        $offer = $this->offerRepository->getById($offerId);
        $this->validateOfferIsEditable($offer);

        // Set offer ID and validate item
        $item->setOfferId($offerId);
        $this->itemValidator->validate($item);

        // Load product data if needed
        $this->enrichItemWithProductData($item);

        // Calculate item totals
        $this->itemCalculator->calculateItemTotals($item);

        // Save item
        $savedItem = $this->offerItemRepository->save($item);

        // Recalculate offer totals
        $this->offerManagement->calculateTotals($offerId);

        // Add to history
        $this->historyManager->addRecord(
            $offerId,
            'item_added',
            sprintf('Item "%s" (SKU: %s) added to offer', $item->getName(), $item->getSku())
        );

        return $this->getItemWithFullData($savedItem->getItemId());
    }

    public function updateItem(int $itemId, OfferItemInterface $item): OfferItemInterface
    {
        // Get existing item
        $existingItem = $this->offerItemRepository->getById($itemId);
        $offerId = $existingItem->getOfferId();

        // Validate offer is editable
        $offer = $this->offerRepository->getById($offerId);
        $this->validateOfferIsEditable($offer);

        // Update item data
        $item->setItemId($itemId);
        $item->setOfferId($offerId);
        $this->itemValidator->validate($item);

        // Calculate item totals
        $this->itemCalculator->calculateItemTotals($item);

        // Save item
        $savedItem = $this->offerItemRepository->save($item);

        // Recalculate offer totals
        $this->offerManagement->calculateTotals($offerId);

        // Add to history
        $this->historyManager->addRecord(
            $offerId,
            'item_updated',
            sprintf('Item "%s" (SKU: %s) updated', $item->getName(), $item->getSku())
        );

        return $this->getItemWithFullData($savedItem->getItemId());
    }

    public function removeItem(int $itemId): bool
    {
        $item = $this->offerItemRepository->getById($itemId);
        $offerId = $item->getOfferId();

        // Validate offer is editable
        $offer = $this->offerRepository->getById($offerId);
        $this->validateOfferIsEditable($offer);

        // Remove item
        $result = $this->offerItemRepository->deleteById($itemId);

        if ($result) {
            // Recalculate offer totals
            $this->offerManagement->calculateTotals($offerId);

            // Add to history
            $this->historyManager->addRecord(
                $offerId,
                'item_removed',
                sprintf('Item "%s" (SKU: %s) removed from offer', $item->getName(), $item->getSku())
            );
        }

        return $result;
    }

    public function duplicateItem(int $itemId): OfferItemInterface
    {
        $originalItem = $this->offerItemRepository->getById($itemId);

        // Create new item with same data
        $newItem = $this->itemFactory->create();
        $newItem->setData($originalItem->getData());
        $newItem->setItemId(null); // Reset ID for new item
        $newItem->setName($originalItem->getName() . ' (Copy)');

        return $this->addItemToOffer($originalItem->getOfferId(), $newItem);
    }

    public function copyItemToOffer(int $itemId, int $targetOfferId): OfferItemInterface
    {
        $originalItem = $this->offerItemRepository->getById($itemId);

        // Create new item for target offer
        $newItem = $this->itemFactory->create();
        $newItem->setData($originalItem->getData());
        $newItem->setItemId(null); // Reset ID for new item

        return $this->addItemToOffer($targetOfferId, $newItem);
    }

    public function updateQuantity(int $itemId, float $qty): OfferItemInterface
    {
        $item = $this->offerItemRepository->getById($itemId);
        $item->setQty($qty);

        return $this->updateItem($itemId, $item);
    }

    public function applyDiscount(int $itemId, float $discountPercent, ?float $discountAmount = null): OfferItemInterface
    {
        $item = $this->offerItemRepository->getById($itemId);
        $item->setDiscountPercent($discountPercent);

        if ($discountAmount !== null) {
            $item->setDiscountAmount($discountAmount);
        }

        return $this->updateItem($itemId, $item);
    }

    public function getItemWithFullData(int $itemId): OfferItemInterface
    {
        $item = $this->offerItemRepository->getById($itemId);

        // Load attachments if the item implements OfferItemDataInterface
        if ($item instanceof \OneMoveTwo\Offers\Api\Data\OfferItemDataInterface) {
            $this->loadItemAttachments($item);
            $this->loadProductData($item);
        }

        return $item;
    }

    public function updateItemOptions(int $itemId, array $productOptions, array $additionalOptions): OfferItemInterface
    {
        $item = $this->offerItemRepository->getById($itemId);

        $item->setProductOptions(json_encode($productOptions));
        $item->setAdditionalOptions(json_encode($additionalOptions));

        return $this->updateItem($itemId, $item);
    }

    private function validateOfferIsEditable($offer): void
    {
        if (in_array($offer->getStatus(), ['completed', 'cancelled', 'expired'])) {
            throw new LocalizedException(__('Cannot modify items in offer with status: %1', $offer->getStatus()));
        }
    }

    private function enrichItemWithProductData(OfferItemInterface $item): void
    {
        if (!$item->getName() || !$item->getSku()) {
            try {
                $product = $this->productRepository->getById($item->getProductId());

                if (!$item->getName()) {
                    $item->setName($product->getName());
                }

                if (!$item->getSku()) {
                    $item->setSku($product->getSku());
                }

                if (!$item->getPrice()) {
                    $item->setPrice($product->getPrice());
                }
            } catch (NoSuchEntityException $e) {
                throw new LocalizedException(__('Product with ID %1 not found', $item->getProductId()));
            }
        }
    }

    private function loadItemAttachments($item): void
    {
        // Implementation would load attachments for the item
        // This requires OfferItemAttachment repository
    }

    private function loadProductData($item): void
    {
        try {
            $product = $this->productRepository->getById($item->getProductId());
            $item->setProductData($product->getData());
        } catch (NoSuchEntityException $e) {
            $item->setProductData(null);
        }
    }
}
