<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Data;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use OneMoveTwo\Offers\Api\Data\OfferItemInterface;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItem as OfferItemResource;

class OfferItem extends AbstractModel implements OfferItemInterface, IdentityInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(OfferItemResource::class);
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG . '_' . $this->getItemId()];
    }

    /**
     * @inheritdoc
     */
    public function getItemId(): ?int
    {
        return $this->getData(self::ITEM_ID) ? (int)$this->getData(self::ITEM_ID) : null;
    }

    /**
     * @inheritdoc
     */
    public function setItemId(int $itemId): OfferItemInterface
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    /**
     * @inheritdoc
     */
    public function getOfferId(): int
    {
        return (int)$this->getData(self::OFFER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOfferId(int $offerId): OfferItemInterface
    {
        return $this->setData(self::OFFER_ID, $offerId);
    }

    /**
     * @inheritdoc
     */
    public function getProductId(): int
    {
        return (int)$this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setProductId(int $productId): OfferItemInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritdoc
     */
    public function getSku(): string
    {
        return (string)$this->getData(self::SKU);
    }

    /**
     * @inheritdoc
     */
    public function setSku(string $sku): OfferItemInterface
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): OfferItemInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getQty(): float
    {
        return (float)$this->getData(self::QTY);
    }

    /**
     * @inheritdoc
     */
    public function setQty(float $qty): OfferItemInterface
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): float
    {
        return (float)$this->getData(self::PRICE);
    }

    /**
     * @inheritdoc
     */
    public function setPrice(float $price): OfferItemInterface
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @inheritdoc
     */
    public function getBasePrice(): ?float
    {
        $basePrice = $this->getData(self::BASE_PRICE);
        return $basePrice !== null ? (float)$basePrice : null;
    }

    /**
     * @inheritdoc
     */
    public function setBasePrice(?float $basePrice): OfferItemInterface
    {
        return $this->setData(self::BASE_PRICE, $basePrice);
    }

    /**
     * @inheritdoc
     */
    public function getDiscountPercent(): ?float
    {
        $discountPercent = $this->getData(self::DISCOUNT_PERCENT);
        return $discountPercent !== null ? (float)$discountPercent : null;
    }

    /**
     * @inheritdoc
     */
    public function setDiscountPercent(?float $discountPercent): OfferItemInterface
    {
        return $this->setData(self::DISCOUNT_PERCENT, $discountPercent);
    }

    /**
     * @inheritdoc
     */
    public function getDiscountAmount(): ?float
    {
        $discountAmount = $this->getData(self::DISCOUNT_AMOUNT);
        return $discountAmount !== null ? (float)$discountAmount : null;
    }

    /**
     * @inheritdoc
     */
    public function setDiscountAmount(?float $discountAmount): OfferItemInterface
    {
        return $this->setData(self::DISCOUNT_AMOUNT, $discountAmount);
    }

    /**
     * @inheritdoc
     */
    public function getRowTotal(): float
    {
        return (float)$this->getData(self::ROW_TOTAL);
    }

    /**
     * @inheritdoc
     */
    public function setRowTotal(float $rowTotal): OfferItemInterface
    {
        return $this->setData(self::ROW_TOTAL, $rowTotal);
    }

    /**
     * @inheritdoc
     */
    public function getIsOptional(): bool
    {
        return (bool)$this->getData(self::IS_OPTIONAL);
    }

    /**
     * @inheritdoc
     */
    public function setIsOptional(bool $isOptional): OfferItemInterface
    {
        return $this->setData(self::IS_OPTIONAL, $isOptional);
    }

    /**
     * @inheritdoc
     */
    public function getHasCustomOptions(): bool
    {
        return (bool)$this->getData(self::HAS_CUSTOM_OPTIONS);
    }

    /**
     * @inheritdoc
     */
    public function setHasCustomOptions(bool $hasCustomOptions): OfferItemInterface
    {
        return $this->setData(self::HAS_CUSTOM_OPTIONS, $hasCustomOptions);
    }

    /**
     * @inheritdoc
     */
    public function getProductOptions(): ?string
    {
        return $this->getData(self::PRODUCT_OPTIONS);
    }

    /**
     * @inheritdoc
     */
    public function setProductOptions(?string $productOptions): OfferItemInterface
    {
        return $this->setData(self::PRODUCT_OPTIONS, $productOptions);
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalOptions(): ?string
    {
        return $this->getData(self::ADDITIONAL_OPTIONS);
    }

    /**
     * @inheritdoc
     */
    public function setAdditionalOptions(?string $additionalOptions): OfferItemInterface
    {
        return $this->setData(self::ADDITIONAL_OPTIONS, $additionalOptions);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): OfferItemInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): OfferItemInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get parsed product options
     *
     * @return array
     */
    public function getParsedProductOptions(): array
    {
        $options = $this->getProductOptions();
        if (!$options) {
            return [];
        }

        $decoded = json_decode($options, true);
        return $decoded ?: [];
    }

    /**
     * Get parsed additional options
     *
     * @return array
     */
    public function getParsedAdditionalOptions(): array
    {
        $options = $this->getAdditionalOptions();
        if (!$options) {
            return [];
        }

        $decoded = json_decode($options, true);
        return $decoded ?: [];
    }

    /**
     * Get item attachments (for API)
     *
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemAttachmentInterface[]
     */
    public function getAttachments(): array
    {
        if (!$this->hasData('attachments')) {
            $this->loadAttachments();
        }
        return $this->getData('attachments') ?? [];
    }

    /**
     * Set item attachments (for API)
     *
     * @param \OneMoveTwo\Offers\Api\Data\OfferItemAttachmentInterface[] $attachments
     * @return $this
     */
    public function setAttachments(array $attachments): self
    {
        return $this->setData('attachments', $attachments);
    }

    /**
     * Load attachments if not loaded
     */
    private function loadAttachments(): void
    {
        if (!$this->getItemId()) {
            $this->setData('attachments', []);
            return;
        }

        // This would be injected in real implementation
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $attachmentRepository = $objectManager->get(\OneMoveTwo\Offers\Api\OfferItemAttachmentRepositoryInterface::class);
        $attachments = $attachmentRepository->getByOfferItemId($this->getItemId());
        $this->setData('attachments', $attachments);
    }

}
