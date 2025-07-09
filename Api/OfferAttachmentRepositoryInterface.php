<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use OneMoveTwo\Offers\Api\Data\OfferAttachmentInterface;

/**
 * Offer Attachment repository interface
 */
interface OfferAttachmentRepositoryInterface
{
    /**
     * Save offer attachment
     *
     * @param OfferAttachmentInterface $offerAttachment
     * @return OfferAttachmentInterface
     * @throws CouldNotSaveException
     */
    public function save(OfferAttachmentInterface $offerAttachment): OfferAttachmentInterface;

    /**
     * Get offer attachment by ID
     *
     * @param int $attachmentId
     * @return OfferAttachmentInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $attachmentId): OfferAttachmentInterface;

    /**
     * Get offer attachments by offer ID
     *
     * @param int $offerId
     * @return OfferAttachmentInterface[]
     */
    public function getByOfferId(int $offerId): array;

    /**
     * Delete offer attachment
     *
     * @param OfferAttachmentInterface $offerAttachment
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(OfferAttachmentInterface $offerAttachment): bool;

    /**
     * Delete offer attachment by ID
     *
     * @param int $attachmentId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $attachmentId): bool;

    /**
     * Get list of offer attachments
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
