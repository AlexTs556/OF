<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Data;

use Magento\Framework\DataObject\IdentityInterface;
use OneMoveTwo\Offers\Api\Data\OfferHistoryInterface;
use OneMoveTwo\Offers\Model\ResourceModel\OfferHistory as OfferHistoryResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Offer History Model
 */
class OfferHistory extends AbstractModel implements OfferHistoryInterface, IdentityInterface
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OfferHistoryResource::class);
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG . '_' . $this->getHistoryId()];
    }

    /**
     * @inheritDoc
     */
    public function getHistoryId(): ?int
    {
        $historyId = $this->getData(self::HISTORY_ID);
        return $historyId ? (int)$historyId : null;
    }

    /**
     * @inheritDoc
     */
    public function setHistoryId(int $historyId): OfferHistoryInterface
    {
        return $this->setData(self::HISTORY_ID, $historyId);
    }

    /**
     * @inheritDoc
     */
    public function getOfferId(): int
    {
        return (int)$this->getData(self::OFFER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOfferId(int $offerId): OfferHistoryInterface
    {
        return $this->setData(self::OFFER_ID, $offerId);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(?string $status): OfferHistoryInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getComment(): ?string
    {
        return $this->getData(self::COMMENT);
    }

    /**
     * @inheritDoc
     */
    public function setComment(?string $comment): OfferHistoryInterface
    {
        return $this->setData(self::COMMENT, $comment);
    }

    /**
     * @inheritDoc
     */
    public function getIsCustomerNotified(): bool
    {
        return (bool)$this->getData(self::IS_CUSTOMER_NOTIFIED);
    }

    /**
     * @inheritDoc
     */
    public function setIsCustomerNotified(bool $isCustomerNotified): OfferHistoryInterface
    {
        return $this->setData(self::IS_CUSTOMER_NOTIFIED, $isCustomerNotified);
    }

    /**
     * @inheritDoc
     */
    public function getVisibleOnStorefront(): bool
    {
        return (bool)$this->getData(self::VISIBLE_ON_STOREFRONT);
    }

    /**
     * @inheritDoc
     */
    public function setVisibleOnStorefront(bool $visibleOnStorefront): OfferHistoryInterface
    {
        return $this->setData(self::VISIBLE_ON_STOREFRONT, $visibleOnStorefront);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedById(): ?int
    {
        $createdById = $this->getData(self::CREATED_BY_ID);
        return $createdById ? (int)$createdById : null;
    }

    /**
     * @inheritDoc
     */
    public function setCreatedById(?int $createdById): OfferHistoryInterface
    {
        return $this->setData(self::CREATED_BY_ID, $createdById);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedByName(): ?string
    {
        return $this->getData(self::CREATED_BY_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedByName(?string $createdByName): OfferHistoryInterface
    {
        return $this->setData(self::CREATED_BY_NAME, $createdByName);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(?string $createdAt): OfferHistoryInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
