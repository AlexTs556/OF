<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api\Data;

/**
 * Offer History interface
 */
interface OfferHistoryInterface
{
    /**
     * const for keys of data array. Identical to the name of the getter in snake case
     */
    public const string CACHE_TAG = 'offer_history';
    public const string HISTORY_ID = 'history_id';
    public const string OFFER_ID = 'offer_id';
    public const string STATUS = 'status';
    public const string COMMENT = 'comment';
    public const string IS_CUSTOMER_NOTIFIED = 'is_customer_notified';
    public const string VISIBLE_ON_STOREFRONT = 'visible_on_storefront';
    public const string CREATED_BY_ID = 'created_by_id';
    public const string CREATED_BY_NAME = 'created_by_name';
    public const string CREATED_AT = 'created_at';

    /**
     * Get history ID
     *
     * @return int|null
     */
    public function getHistoryId(): ?int;

    /**
     * Set history ID
     *
     * @param int $historyId
     * @return $this
     */
    public function setHistoryId(int $historyId): self;

    /**
     * Get offer ID
     *
     * @return int
     */
    public function getOfferId(): int;

    /**
     * Set offer ID
     *
     * @param int $offerId
     * @return $this
     */
    public function setOfferId(int $offerId): self;

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status
     *
     * @param string|null $status
     * @return $this
     */
    public function setStatus(?string $status): self;

    /**
     * Get comment
     *
     * @return string|null
     */
    public function getComment(): ?string;

    /**
     * Set comment
     *
     * @param string|null $comment
     * @return $this
     */
    public function setComment(?string $comment): self;

    /**
     * Get is customer notified
     *
     * @return bool
     */
    public function getIsCustomerNotified(): bool;

    /**
     * Set is customer notified
     *
     * @param bool $isCustomerNotified
     * @return $this
     */
    public function setIsCustomerNotified(bool $isCustomerNotified): self;

    /**
     * Get visible on storefront
     *
     * @return bool
     */
    public function getVisibleOnStorefront(): bool;

    /**
     * Set visible on storefront
     *
     * @param bool $visibleOnStorefront
     * @return $this
     */
    public function setVisibleOnStorefront(bool $visibleOnStorefront): self;

    /**
     * Get created by ID
     *
     * @return int|null
     */
    public function getCreatedById(): ?int;

    /**
     * Set created by ID
     *
     * @param int|null $createdById
     * @return $this
     */
    public function setCreatedById(?int $createdById): self;

    /**
     * Get created by name
     *
     * @return string|null
     */
    public function getCreatedByName(): ?string;

    /**
     * Set created by name
     *
     * @param string|null $createdByName
     * @return $this
     */
    public function setCreatedByName(?string $createdByName): self;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?string $createdAt): self;
}
