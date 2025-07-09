<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api\Data;

interface OfferInterface
{
    public const string CACHE_TAG = 'offer';

    public const string ENTITY_ID = 'entity_id';
    public const string OFFER_NUMBER = 'offer_number';
    public const string QUOTE_ID = 'quote_id';
    public const string ORDER_ID = 'order_id';
    public const string OFFER_NAME = 'offer_name';
    public const string CUSTOMER_ID = 'customer_id';
    public const string CUSTOMER_IS_GUEST = 'customer_is_guest';
    public const string CUSTOMER_EMAIL = 'customer_email';
    public const string CUSTOMER_NAME = 'customer_name';
    public const string STATUS = 'status';
    public const string VERSION = 'version';
    public const string PARENT_OFFER_ID = 'parent_offer_id';
    public const string ADMIN_CREATOR_ID = 'admin_creator_id';
    public const string STORE_ID = 'store_id';
    public const string SUBTOTAL = 'subtotal';
    public const string DISCOUNT_AMOUNT = 'discount_amount';
    public const string SHIPPING_AMOUNT = 'shipping_amount';
    public const string TAX_AMOUNT = 'tax_amount';
    public const string GRAND_TOTAL = 'grand_total';
    public const string PREPAYMENT_AMOUNT = 'prepayment_amount';
    public const string PREPAYMENT_PERCENT = 'prepayment_percent';
    public const string ITEMS_COUNT = 'items_count';
    public const string ITEMS_QTY = 'items_qty';
    public const string EXPIRY_DATE = 'expiry_date';
    public const string CREATED_AT = 'created_at';
    public const string UPDATED_AT = 'updated_at';

    public function getEntityId(): ?int;

    public function getOfferNumber(): string;
    public function setOfferNumber(string $offerNumber): self;

    public function getOfferName(): string;
    public function setOfferName(string $offerName): self;

    public function getQuoteId(): ?int;
    public function setQuoteId(?int $quoteId): self;

    public function getOrderId(): ?int;
    public function setOrderId(?int $orderId): self;

    public function getCustomerId(): ?int;
    public function setCustomerId(?int $customerId): self;

    public function getCustomerIsGuest(): bool;
    public function setCustomerIsGuest(bool $isGuest): self;

    public function getCustomerEmail(): ?string;
    public function setCustomerEmail(?string $email): self;

    public function getCustomerName(): ?string;
    public function setCustomerName(?string $name): self;

    public function getStatus(): string;
    public function setStatus(string $status): self;

    public function getVersion(): int;
    public function setVersion(int $version): self;

    public function getParentOfferId(): ?int;
    public function setParentOfferId(?int $parentId): self;

    public function getAdminCreatorId(): int;
    public function setAdminCreatorId(int $adminId): self;

    public function getStoreId(): int;
    public function setStoreId(int $storeId): self;

    public function getSubtotal(): float;
    public function setSubtotal(float $subtotal): self;

    public function getDiscountAmount(): float;
    public function setDiscountAmount(float $amount): self;

    public function getShippingAmount(): float;
    public function setShippingAmount(float $amount): self;

    public function getTaxAmount(): float;
    public function setTaxAmount(float $amount): self;

    public function getGrandTotal(): float;
    public function setGrandTotal(float $total): self;

    public function getPrepaymentAmount(): float;
    public function setPrepaymentAmount(float $amount): self;

    public function getPrepaymentPercent(): float;
    public function setPrepaymentPercent(float $percent): self;

    public function getItemsCount(): int;
    public function setItemsCount(int $count): self;

    public function getItemsQty(): float;
    public function setItemsQty(float $qty): self;

    public function getExpiryDate(): ?string;
    public function setExpiryDate(?string $date): self;

    public function getCreatedAt(): string;
    public function setCreatedAt(string $date): self;

    public function getUpdatedAt(): string;
    public function setUpdatedAt(string $date): self;
}
