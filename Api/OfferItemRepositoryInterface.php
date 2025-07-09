<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use OneMoveTwo\Offers\Api\Data\OfferItemInterface;
use OneMoveTwo\Offers\Api\Data\OfferItemSearchResultsInterface;

interface OfferItemRepositoryInterface
{
    /**
     * Save offer item
     *
     * @param OfferItemInterface $offerItem
     * @return OfferItemInterface
     * @throws LocalizedException
     */
    public function save(OfferItemInterface $offerItem): OfferItemInterface;

    /**
     * Get offer item by ID
     *
     * @param int $itemId
     * @return OfferItemInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $itemId): OfferItemInterface;

    /**
     * Get offer items by offer ID
     *
     * @param int $offerId
     * @return OfferItemInterface[]
     */
    public function getByOfferId(int $offerId): array;

    /**
     * Get offer items by product ID
     *
     * @param int $productId
     * @return OfferItemInterface[]
     */
    public function getByProductId(int $productId): array;

    /**
     * Get offer items list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return OfferItemSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): OfferItemSearchResultsInterface;

    /**
     * Delete offer item
     *
     * @param OfferItemInterface $offerItem
     * @return bool
     * @throws LocalizedException
     */
    public function delete(OfferItemInterface $offerItem): bool;

    /**
     * Delete offer item by ID
     *
     * @param int $itemId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $itemId): bool;

    /**
     * Delete all items by offer ID
     *
     * @param int $offerId
     * @return bool
     * @throws LocalizedException
     */
    public function deleteByOfferId(int $offerId): bool;
}
