<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use OneMoveTwo\Offers\Api\Data\OfferInterface;
use OneMoveTwo\Offers\Api\Data\OfferInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Api\OfferItemRepositoryInterface;
use OneMoveTwo\Offers\Api\OfferManagementInterface;
//use OneMoveTwo\Offers\Service\Calculator\TotalsCalculator;
//use OneMoveTwo\Offers\Service\Email\OfferEmailSender;
use OneMoveTwo\Offers\Service\History\OfferHistoryManagementService;
use OneMoveTwo\Offers\Service\Validation\OfferValidator;

readonly class OfferManagementService implements OfferManagementInterface
{
    public function __construct(
        private OfferRepositoryInterface     $offerRepository,
        private OfferItemRepositoryInterface $offerItemRepository,
        private OfferInterfaceFactory        $offerFactory,
        private OfferAttachmentManagementService  $offerAttachmentManagementService,
       /* private TotalsCalculator             $totalsCalculator,
        private OfferEmailSender             $emailSender,
        private OfferHistoryManager          $historyManager*/
        private OfferHistoryManagementService $historyManager,
        private OfferValidator               $offerValidator,
        private ScopeConfigInterface         $scopeConfig,
        private StoreManagerInterface        $storeManager
    ) {
    }

    public function createOffer(
        OfferInterface $offer,
        array $items = [],
        array $attachments = []
    ): OfferInterface {
        // Validate offer data
        $this->offerValidator->validate($offer);

        // Generate offer number if not provided or auto-generate is requested
        if (!$offer->getOfferNumber() || $offer->getData('auto_generate_number')) {
            $offer->setOfferNumber($this->generateOfferNumber());
        }

        // Save offer
        $savedOffer = $this->offerRepository->save($offer);

        // Add items
        if (!empty($items)) {
            foreach ($items as $item) {
                $item->setOfferId($savedOffer->getEntityId());
                $this->offerItemRepository->save($item);
            }
        }

        // Process attachments if provided
        if (!empty($attachments)) {
            $this->processAttachments($savedOffer->getEntityId(), $attachments);
        }

        // Calculate totals
        //$savedOffer = $this->calculateTotals($savedOffer->getEntityId());

        // Add to history
        $this->historyManager->addRecord(
            $savedOffer->getEntityId(),
            'created',
            'Offer created'
        );

        return $savedOffer;
    }

    public function updateOffer(
        OfferInterface $offer,
        array $items = [],
        array $attachments = []
    ): OfferInterface {

        if (!$offer->getEntityId()){

        }

        // Validate offer data
        $this->offerValidator->validate($offer);

        // Check if auto-generate number is requested
        if ($offer->getData('auto_generate_number')) {
            // Generate a new offer number using the template
            $offer->setOfferNumber($this->generateOfferNumber());
        }

        // Update offer
        $savedOffer = $this->offerRepository->save($offer);

        // Update items if provided
        if (!empty($items)) {
            $this->updateOfferItems($savedOffer->getEntityId(), $items);
        }

        // Process attachments if provided
        if (!empty($attachments)) {
            $this->processAttachments($savedOffer, $attachments);
        }

        // Recalculate totals
        //$savedOffer = $this->calculateTotals($savedOffer->getEntityId());

        // Add to history
        $this->historyManager->addRecord(
            $savedOffer->getEntityId(),
            'updated',
            'Offer updated'
        );

        return $savedOffer;
    }

    public function addItemToOffer(int $offerId, $item): \OneMoveTwo\Offers\Api\Data\OfferItemInterface
    {
        // Validate offer exists
        $offer = $this->offerRepository->getById($offerId);

        // Set offer ID
        $item->setOfferId($offerId);

        // Validate item
        $this->offerValidator->validateItem($item);

        // Save item
        $savedItem = $this->offerItemRepository->save($item);

        // Recalculate totals
        //$this->calculateTotals($offerId);

        // Add to history
        $this->historyManager->addRecord(
            $offerId,
            'item_added',
            sprintf('Item %s added to offer', $item->getName())
        );

        return $savedItem;
    }

    public function updateOfferItem(int $itemId, $item): \OneMoveTwo\Offers\Api\Data\OfferItemInterface
    {
        // Validate item exists
        $existingItem = $this->offerItemRepository->getById($itemId);

        // Update item data
        $item->setItemId($itemId);
        $item->setOfferId($existingItem->getOfferId());

        // Validate item
        $this->offerValidator->validateItem($item);

        // Save item
        $savedItem = $this->offerItemRepository->save($item);

        // Recalculate totals
       // $this->calculateTotals($existingItem->getOfferId());

        // Add to history
        $this->historyManager->addRecord(
            $existingItem->getOfferId(),
            'item_updated',
            sprintf('Item %s updated', $item->getName())
        );

        return $savedItem;
    }

    public function removeItemFromOffer(int $itemId): bool
    {
        // Get item
        $item = $this->offerItemRepository->getById($itemId);
        $offerId = $item->getOfferId();

        // Delete item
        $result = $this->offerItemRepository->deleteById($itemId);

        if ($result) {
            // Recalculate totals
           // $this->calculateTotals($offerId);

            // Add to history
            $this->historyManager->addRecord(
                $offerId,
                'item_removed',
                sprintf('Item %s removed from offer', $item->getName())
            );
        }

        return $result;
    }

    public function changeOfferStatus(int $offerId, string $status, ?string $comment = null): OfferInterface
    {
        // Get offer
        $offer = $this->offerRepository->getById($offerId);

        // Validate status transition
        $this->offerValidator->validateStatusChange($offer->getStatus(), $status);

        // Update status
        $oldStatus = $offer->getStatus();
        $offer->setStatus($status);
        $savedOffer = $this->offerRepository->save($offer);

        // Add to history
        $this->historyManager->addRecord(
            $offerId,
            $status,
            $comment ?: sprintf('Status changed from %s to %s', $oldStatus, $status)
        );

        return $savedOffer;
    }

    public function createOfferVersion(int $parentOfferId): OfferInterface
    {
        // Get parent offer
        $parentOffer = $this->offerRepository->getById($parentOfferId);

        // Create new offer as version
        $newOffer = $this->offerFactory->create();
        $newOffer->setData($parentOffer->getData());
        $newOffer->setEntityId(null);
        $newOffer->setParentOfferId($parentOfferId);
        $newOffer->setVersion($parentOffer->getVersion() + 1);
        $newOffer->setOfferNumber($this->generateVersionNumber($parentOffer->getOfferNumber()));
        $newOffer->setStatus('draft');

        // Save new version
        $savedOffer = $this->offerRepository->save($newOffer);

        // Copy items
        $this->copyOfferItems($parentOfferId, $savedOffer->getEntityId());

        // Copy attachments
        $this->copyOfferAttachments($parentOfferId, $savedOffer->getEntityId());

        // Add to history
        $this->historyManager->addRecord(
            $savedOffer->getEntityId(),
            'version_created',
            sprintf('New version created from offer %s', $parentOffer->getOfferNumber())
        );

        return $savedOffer;
    }

    public function sendOfferEmail(int $offerId): bool
    {
        // Get offer
        $offer = $this->offerRepository->getById($offerId);

        // Send email
        $result = $this->emailSender->sendOfferEmail($offer);

        if ($result) {
            // Update offer email sent status
            $offer->setEmailSentAt(date('Y-m-d H:i:s'));
            $this->offerRepository->save($offer);

            // Add to history
            $this->historyManager->addRecord(
                $offerId,
                'email_sent',
                'Offer email sent to customer'
            );
        }

        return $result;
    }

    public function calculateTotals(int $offerId): OfferInterface
    {
        // Get offer
        $offer = $this->offerRepository->getById($offerId);

        // Calculate totals
        $totals = $this->totalsCalculator->calculate($offer);

        // Update offer with calculated totals
        $offer->setSubtotal($totals['subtotal']);
        $offer->setDiscountAmount($totals['discount_amount']);
        $offer->setShippingAmount($totals['shipping_amount']);
        $offer->setTaxAmount($totals['tax_amount']);
        $offer->setGrandTotal($totals['grand_total']);
        $offer->setItemsCount($totals['items_count']);
        $offer->setItemsQty($totals['items_qty']);

        return $this->offerRepository->save($offer);
    }

    // Private helper methods
    private function generateOfferNumber(): string
    {
        // Check if auto-generate is enabled
        $autoGenerate = $this->scopeConfig->getValue('offers_general/general/auto_generate_number');
        if (!$autoGenerate) {
            // Fallback to the old method if auto-generate is disabled
            return 'OF' . date('Y') . str_pad((string)rand(1, 99999), 5, '0', STR_PAD_LEFT);
        }

        // Get the template from configuration
        $template = $this->scopeConfig->getValue('offers_general/general/offer_number_template');
        if (empty($template)) {
            $template = 'OF-{store}-{datetime}-{version}'; // Default template
        }

        // Get current store ID
        $storeId = $this->storeManager->getStore()->getId();

        // Generate datetime with milliseconds
        $datetime = date('YmdHis') . substr(microtime(), 2, 3); // Format: YYYYMMDDHHMMSSmmm

        // Generate a unique version identifier
        $version = uniqid();

        // Replace placeholders
        $offerNumber = str_replace(
            ['{store}', '{datetime}', '{version}'],
            [$storeId, $datetime, $version],
            $template
        );

        return $offerNumber;
    }

    private function generateVersionNumber(string $baseNumber): string
    {
        return $baseNumber . '-V' . time();
    }

    private function updateOfferItems(int $offerId, array $items): void
    {
        // Implementation for updating multiple items
        foreach ($items as $item) {
            $item->setOfferId($offerId);
            $this->offerItemRepository->save($item);
        }
    }

    /**
     * @throws LocalizedException
     */
    private function processAttachments(OfferInterface $offer, array $attachments)
    {
        if (isset($attachments['add'])) {
            $this->offerAttachmentManagementService->processFileUploads(
                (int)$offer->getId(),
                $attachments['add'],
                $attachments['add']
            );
        }

        if (isset($attachments['remove'])) {
            foreach ($attachments['remove'] as $attachmentId) {
                $this->offerAttachmentManagementService->removeAttachment((int)$attachmentId);
            }
        }
    }

    private function copyOfferItems(int $fromOfferId, int $toOfferId): void
    {
        // Implementation for copying items between offers
    }

    private function copyOfferAttachments(int $fromOfferId, int $toOfferId): void
    {
        // Implementation for copying attachments between offers
    }
}
