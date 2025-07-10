<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api;

use OneMoveTwo\Offers\Api\Data\OfferInterface;

interface OfferManagementInterface
{
    /**
     * Create new offer with all data
     *
     * @param \OneMoveTwo\Offers\Api\Data\OfferInterface $offer
     * @param \OneMoveTwo\Offers\Api\Data\OfferItemInterface[] $items
     * @param \OneMoveTwo\Offers\Api\Data\OfferAttachmentInterface[] $attachments
     * @return \OneMoveTwo\Offers\Api\Data\OfferInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOffer(
        OfferInterface $offer,
        array $items = [],
        array $attachments = []
    ): OfferInterface;

    /**
     * Update existing offer with all data
     *
     * @param \OneMoveTwo\Offers\Api\Data\OfferInterface $offer
     * @param \OneMoveTwo\Offers\Api\Data\OfferItemInterface[] $items
     * @param \OneMoveTwo\Offers\Api\Data\OfferAttachmentInterface[] $attachments
     * @return \OneMoveTwo\Offers\Api\Data\OfferInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateOffer(
        OfferInterface $offer,
        array $items = [],
        array $attachments = []
    ): OfferInterface;

    /**
     * Add item to offer
     *
     * @param int $offerId
     * @param \OneMoveTwo\Offers\Api\Data\OfferItemInterface $item
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addItemToOffer(int $offerId, $item);

    /**
     * Update offer item
     *
     * @param int $itemId
     * @param \OneMoveTwo\Offers\Api\Data\OfferItemInterface $item
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateOfferItem(int $itemId, $item);

    /**
     * Remove item from offer
     *
     * @param int $itemId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeItemFromOffer(int $itemId): bool;

    /**
     * Change offer status
     *
     * @param int $offerId
     * @param string $status
     * @param string|null $comment
     * @return \OneMoveTwo\Offers\Api\Data\OfferInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function changeOfferStatus(int $offerId, string $status, ?string $comment = null): OfferInterface;

    /**
     * Create new offer version
     *
     * @param int $parentOfferId
     * @return \OneMoveTwo\Offers\Api\Data\OfferInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOfferVersion(int $parentOfferId): OfferInterface;

    /**
     * Send offer email
     *
     * @param int $offerId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendOfferEmail(int $offerId): bool;

    /**
     * Calculate offer totals
     *
     * @param int $offerId
     * @return \OneMoveTwo\Offers\Api\Data\OfferInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function calculateTotals(int $offerId): OfferInterface;
}
