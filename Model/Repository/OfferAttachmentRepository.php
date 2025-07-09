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
use OneMoveTwo\Offers\Api\Data\OfferAttachmentInterface;
use OneMoveTwo\Offers\Api\Data\OfferAttachmentInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferAttachmentRepositoryInterface;
use OneMoveTwo\Offers\Model\ResourceModel\OfferAttachment as OfferAttachmentResource;
use OneMoveTwo\Offers\Model\ResourceModel\OfferAttachment\CollectionFactory as OfferAttachmentCollectionFactory;

/**
 * Offer Attachment Repository
 */
readonly class OfferAttachmentRepository implements OfferAttachmentRepositoryInterface
{
    /**
     * @param OfferAttachmentResource $resource
     * @param OfferAttachmentInterfaceFactory $offerAttachmentFactory
     * @param OfferAttachmentCollectionFactory $offerAttachmentCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private OfferAttachmentResource          $resource,
        private OfferAttachmentInterfaceFactory  $offerAttachmentFactory,
        private OfferAttachmentCollectionFactory $offerAttachmentCollectionFactory,
        private SearchResultsInterfaceFactory    $searchResultsFactory,
        private CollectionProcessorInterface     $collectionProcessor,
        private SearchCriteriaBuilder            $searchCriteriaBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(OfferAttachmentInterface $offerAttachment): OfferAttachmentInterface
    {
        try {
            $this->resource->save($offerAttachment);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the offer attachment: %1', $exception->getMessage()),
                $exception
            );
        }
        return $offerAttachment;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $attachmentId): OfferAttachmentInterface
    {
        $offerAttachment = $this->offerAttachmentFactory->create();
        $this->resource->load($offerAttachment, $attachmentId);
        if (!$offerAttachment->getAttachmentId()) {
            throw new NoSuchEntityException(__('Offer Attachment with id "%1" does not exist.', $attachmentId));
        }
        return $offerAttachment;
    }

    /**
     * @inheritDoc
     */
    public function getByOfferId(int $offerId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OfferAttachmentInterface::OFFER_ID, $offerId)
            ->addFilter('sort_order', 'ASC', 'direction')
            ->create();

        $searchResults = $this->getList($searchCriteria);
        return $searchResults->getItems();
    }

    /**
     * @inheritDoc
     */
    public function delete(OfferAttachmentInterface $offerAttachment): bool
    {
        try {
            $this->resource->delete($offerAttachment);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the offer attachment: %1', $exception->getMessage())
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
        $collection = $this->offerAttachmentCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }
}
