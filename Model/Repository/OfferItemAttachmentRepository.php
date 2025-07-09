<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Repository;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use OneMoveTwo\Offers\Api\Data\OfferItemAttachmentInterface;
use OneMoveTwo\Offers\Api\Data\OfferItemAttachmentInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferItemAttachmentRepositoryInterface;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItemAttachment as OfferItemAttachmentResource;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItemAttachment\CollectionFactory as OfferItemAttachmentCollectionFactory;

/**
 * Offer Item Attachment Repository
 */
readonly class OfferItemAttachmentRepository implements OfferItemAttachmentRepositoryInterface
{
    public function __construct(
        private OfferItemAttachmentResource $resource,
        private OfferItemAttachmentInterfaceFactory $offerItemAttachmentFactory,
        private OfferItemAttachmentCollectionFactory $offerItemAttachmentCollectionFactory,
        private SearchResultsInterfaceFactory $searchResultsFactory,
        private CollectionProcessorInterface $collectionProcessor,
        private SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(OfferItemAttachmentInterface $offerItemAttachment): OfferItemAttachmentInterface
    {
        try {
            $this->resource->save($offerItemAttachment);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the offer item attachment: %1', $exception->getMessage()),
                $exception
            );
        }
        return $offerItemAttachment;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $attachmentId): OfferItemAttachmentInterface
    {
        $offerItemAttachment = $this->offerItemAttachmentFactory->create();
        $this->resource->load($offerItemAttachment, $attachmentId);
        if (!$offerItemAttachment->getAttachmentId()) {
            throw new NoSuchEntityException(__('Offer Item Attachment with id "%1" does not exist.', $attachmentId));
        }
        return $offerItemAttachment;
    }

    /**
     * @inheritDoc
     */
    public function getByOfferItemsId(int $offerItemsId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OfferItemAttachmentInterface::OFFER_ITEMS_ID, $offerItemsId)
            ->addFilter('sort_order', 'ASC', 'direction')
            ->create();

        $searchResults = $this->getList($searchCriteria);
        return $searchResults->getItems();
    }

    /**
     * @inheritDoc
     */
    public function delete(OfferItemAttachmentInterface $offerItemAttachment): bool
    {
        try {
            $this->resource->delete($offerItemAttachment);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the offer item attachment: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $attachmentId): bool
    {
        return $this->delete($this->getById($attachmentId));
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->offerItemAttachmentCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }
}
