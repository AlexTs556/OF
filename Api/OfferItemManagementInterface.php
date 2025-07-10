<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api;

use OneMoveTwo\Offers\Api\Data\OfferItemInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface OfferItemManagementInterface
{
    /**
     * Add item to offer with validation and recalculation
     *
     * @param int $offerId
     * @param \OneMoveTwo\Offers\Api\Data\OfferItemInterface $item
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface
     * @throws LocalizedException
     */
    public function addItemToOffer(int $offerId, OfferItemInterface $item): OfferItemInterface;

    /**
     * Update offer item with validation and recalculation
     *
     * @param int $itemId
     * @param \OneMoveTwo\Offers\Api\Data\OfferItemInterface $item
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface
     * @throws LocalizedException
     */
    public function updateItem(int $itemId, OfferItemInterface $item): OfferItemInterface;

    /**
     * Remove item from offer with recalculation
     *
     * @param int $itemId
     * @return bool
     * @throws LocalizedException
     */
    public function removeItem(int $itemId): bool;

    /**
     * Duplicate item within same offer
     *
     * @param int $itemId
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface
     * @throws LocalizedException
     */
    public function duplicateItem(int $itemId): OfferItemInterface;

    /**
     * Copy item to another offer
     *
     * @param int $itemId
     * @param int $targetOfferId
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface
     * @throws LocalizedException
     */
    public function copyItemToOffer(int $itemId, int $targetOfferId): OfferItemInterface;

    /**
     * Update item quantity with price recalculation
     *
     * @param int $itemId
     * @param float $qty
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface
     * @throws LocalizedException
     */
    public function updateQuantity(int $itemId, float $qty): OfferItemInterface;

    /**
     * Apply discount to item
     *
     * @param int $itemId
     * @param float $discountPercent
     * @param float|null $discountAmount
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface
     * @throws LocalizedException
     */
    public function applyDiscount(int $itemId, float $discountPercent, ?float $discountAmount = null): OfferItemInterface;

    /**
     * Get item with full data including attachments
     *
     * @param int $itemId
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface
     * @throws NoSuchEntityException
     */
    public function getItemWithFullData(int $itemId): OfferItemInterface;

    /**
     * Update item options (customization)
     *
     * @param int $itemId
     * @param array $productOptions
     * @param array $additionalOptions
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface
     * @throws LocalizedException
     */
    public function updateItemOptions(int $itemId, array $productOptions, array $additionalOptions): OfferItemInterface;
}
