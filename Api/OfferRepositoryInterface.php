<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use OneMoveTwo\Offers\Api\Data\OfferInterface;

/**
 * Interface OfferRepositoryInterface
 * @api
 */
interface OfferRepositoryInterface
{
    /**
     * Save offer
     *
     * @param OfferInterface $offer
     * @return OfferInterface
     */
    public function save(OfferInterface $offer): OfferInterface;

    /**
     * Get offer by ID
     *
     * @param int $id
     * @return OfferInterface
     */
    public function getById(int $id): OfferInterface;

    /**
     * @param string $offerNumber
     * @return OfferInterface
     * @throws NoSuchEntityException
     */
    public function getByOfferNumber(string $offerNumber): OfferInterface;

    /**
     * Get list by search criteria
     *
     * @param SearchCriteriaInterface $criteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;

    /**
     * Delete offer
     *
     * @param OfferInterface $offer
     * @return bool true on success
     */
    public function delete(OfferInterface $offer): bool;

    /**
     * Delete offer by ID
     *
     * @param int $id
     * @return bool true on success
     */
    public function deleteById(int $id): bool;
}
