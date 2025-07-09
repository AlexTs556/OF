<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api;

use OneMovetwo\Offers\Api\Data\OfferHistoryInterface;
use OneMovetwo\Offers\Api\Data\OfferHistorySearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Offer History CRUD interface
 */
interface OfferHistoryRepositoryInterface
{
    /**
     * Save offer history
     *
     * @param OfferHistoryInterface $offerHistory
     * @return OfferHistoryInterface
     * @throws LocalizedException
     */
    public function save(OfferHistoryInterface $offerHistory): OfferHistoryInterface;

    /**
     * Retrieve offer history
     *
     * @param int $historyId
     * @return OfferHistoryInterface
     * @throws LocalizedException
     */
    public function get(int $historyId): OfferHistoryInterface;

    /**
     * Retrieve offer history matching the specified criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return OfferHistorySearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): OfferHistorySearchResultsInterface;

    /**
     * Delete offer history
     *
     * @param OfferHistoryInterface $offerHistory
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(OfferHistoryInterface $offerHistory): bool;

    /**
     * Delete offer history by ID
     *
     * @param int $historyId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $historyId): bool;

    /**
     * Get offer history by offer ID
     *
     * @param int $offerId
     * @return OfferHistoryInterface[]
     * @throws LocalizedException
     */
    public function getByOfferId(int $offerId): array;
}
