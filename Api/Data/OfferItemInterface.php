<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api\Data;

interface OfferItemInterface
{
    public const string CACHE_TAG = 'offer_item';
    public const string ITEM_ID = 'item_id';
    public const string OFFER_ID = 'offer_id';
    public const string PRODUCT_ID = 'product_id';
    public const string SKU = 'sku';
    public const string NAME = 'name';
    public const string QTY = 'qty';
    public const string PRICE = 'price';
    public const string BASE_PRICE = 'base_price';
    public const string DISCOUNT_PERCENT = 'discount_percent';
    public const string DISCOUNT_AMOUNT = 'discount_amount';
    public const string ROW_TOTAL = 'row_total';
    public const string IS_OPTIONAL = 'is_optional';
    public const string HAS_CUSTOM_OPTIONS = 'has_custom_options';
    public const string PRODUCT_OPTIONS = 'product_options';
    public const string ADDITIONAL_OPTIONS = 'additional_options';
    public const string CREATED_AT = 'created_at';
    public const string UPDATED_AT = 'updated_at';

    /**
     * Get item ID
     *
     * @return int|null
     */
    public function getItemId(): ?int;

    /**
     * Set item ID
     *
     * @param int $itemId
     * @return $this
     */
    public function setItemId(int $itemId): self;

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
     * Get product ID
     *
     * @return int
     */
    public function getProductId(): int;

    /**
     * Set product ID
     *
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * Get SKU
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * Get product name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set product name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Get quantity
     *
     * @return float
     */
    public function getQty(): float;

    /**
     * Set quantity
     *
     * @param float $qty
     * @return $this
     */
    public function setQty(float $qty): self;

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Set price
     *
     * @param float $price
     * @return $this
     */
    public function setPrice(float $price): self;

    /**
     * Get base price
     *
     * @return float|null
     */
    public function getBasePrice(): ?float;

    /**
     * Set base price
     *
     * @param float|null $basePrice
     * @return $this
     */
    public function setBasePrice(?float $basePrice): self;

    /**
     * Get discount percent
     *
     * @return float|null
     */
    public function getDiscountPercent(): ?float;

    /**
     * Set discount percent
     *
     * @param float|null $discountPercent
     * @return $this
     */
    public function setDiscountPercent(?float $discountPercent): self;

    /**
     * Get discount amount
     *
     * @return float|null
     */
    public function getDiscountAmount(): ?float;

    /**
     * Set discount amount
     *
     * @param float|null $discountAmount
     * @return $this
     */
    public function setDiscountAmount(?float $discountAmount): self;

    /**
     * Get row total
     *
     * @return float
     */
    public function getRowTotal(): float;

    /**
     * Set row total
     *
     * @param float $rowTotal
     * @return $this
     */
    public function setRowTotal(float $rowTotal): self;

    /**
     * Get is optional flag
     *
     * @return bool
     */
    public function getIsOptional(): bool;

    /**
     * Set is optional flag
     *
     * @param bool $isOptional
     * @return $this
     */
    public function setIsOptional(bool $isOptional): self;

    /**
     * Get has custom options flag
     *
     * @return bool
     */
    public function getHasCustomOptions(): bool;

    /**
     * Set has custom options flag
     *
     * @param bool $hasCustomOptions
     * @return $this
     */
    public function setHasCustomOptions(bool $hasCustomOptions): self;

    /**
     * Get product options
     *
     * @return string|null
     */
    public function getProductOptions(): ?string;

    /**
     * Set product options
     *
     * @param string|null $productOptions
     * @return $this
     */
    public function setProductOptions(?string $productOptions): self;

    /**
     * Get additional options
     *
     * @return string|null
     */
    public function getAdditionalOptions(): ?string;

    /**
     * Set additional options
     *
     * @param string|null $additionalOptions
     * @return $this
     */
    public function setAdditionalOptions(?string $additionalOptions): self;

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get updated at
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;
}
