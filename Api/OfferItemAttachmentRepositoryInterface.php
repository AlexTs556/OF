<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use OneMoveTwo\Offers\Api\Data\OfferItemAttachmentInterface;

/**
 * Offer Item Attachment repository interface
 */
interface OfferItemAttachmentRepositoryInterface
{
    /**
     * Save offer item attachment
     *
     * @param OfferItemAttachmentInterface $offerItemAttachment
     * @return OfferItemAttachmentInterface
     * @throws CouldNotSaveException
     */
    public function save(OfferItemAttachmentInterface $offerItemAttachment): OfferItemAttachmentInterface;

    /**
     * Get offer item attachment by ID
     *
     * @param int $attachmentId
     * @return OfferItemAttachmentInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $attachmentId): OfferItemAttachmentInterface;

    /**
     * Get offer item attachments by offer item ID
     *
     * @param int $offerItemsId
     * @return OfferItemAttachmentInterface[]
     */
    public function getByOfferItemsId(int $offerItemsId): array;

    /**
     * Delete offer item attachment
     *
     * @param OfferItemAttachmentInterface $offerItemAttachment
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(OfferItemAttachmentInterface $offerItemAttachment): bool;

    /**
     * Delete offer item attachment by ID
     *
     * @param int $attachmentId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $attachmentId): bool;

    /**
     * Get list of offer item attachments
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
