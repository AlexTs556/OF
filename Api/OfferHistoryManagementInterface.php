<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api;

use OneMoveTwo\Offers\Api\Data\OfferHistoryInterface;
use Magento\Framework\Exception\LocalizedException;

interface OfferHistoryManagementInterface
{
    /**
     * Add history record with automatic user detection
     *
     * @param int $offerId
     * @param string $status
     * @param string|null $comment
     * @param bool $isCustomerNotified
     * @param bool $visibleOnStorefront
     * @return OfferHistoryInterface
     * @throws LocalizedException
     */
    public function addRecord(
        int $offerId,
        string $status,
        ?string $comment = null,
        bool $isCustomerNotified = false,
        bool $visibleOnStorefront = false
    ): OfferHistoryInterface;

    /**
     * Get history with pagination and filtering
     *
     * @param int $offerId
     * @param bool $customerVisibleOnly
     * @param int $limit
     * @param int $offset
     * @return OfferHistoryInterface[]
     */
    public function getOfferHistory(
        int $offerId,
        bool $customerVisibleOnly = false,
        int $limit = 50,
        int $offset = 0
    ): array;

    /**
     * Update history record visibility
     *
     * @param int $historyId
     * @param bool $visibleOnStorefront
     * @return OfferHistoryInterface
     * @throws LocalizedException
     */
    public function updateVisibility(int $historyId, bool $visibleOnStorefront): OfferHistoryInterface;
}
