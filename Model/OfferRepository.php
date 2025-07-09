<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use OneMoveTwo\Offers\Api\Data\OfferInterface;
use OneMoveTwo\Offers\Api\Data\OfferInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Model\ResourceModel\Offer as OfferResource;
use OneMoveTwo\Offers\Model\ResourceModel\Offer\Collection as OfferCollection;
use OneMoveTwo\Offers\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItem\CollectionFactory as OfferItemCollectionFactory;
use OneMoveTwo\Offers\Model\ResourceModel\OfferHistory\CollectionFactory as OfferHistoryCollectionFactory;
use OneMoveTwo\Offers\Model\ResourceModel\OfferAttachment\CollectionFactory as OfferAttachmentCollectionFactory;
use OneMoveTwo\Offers\Api\Data\OfferSearchResultsInterface;

readonly class OfferRepository implements OfferRepositoryInterface
{
    public function __construct(
        private OfferResource $resource,
        private OfferInterfaceFactory $offerFactory,
        private OfferCollectionFactory $offerCollectionFactory,
        private OfferItemCollectionFactory $itemCollectionFactory,
        private OfferHistoryCollectionFactory $historyCollectionFactory,
        private OfferAttachmentCollectionFactory $attachmentCollectionFactory,
        private SearchResultsInterfaceFactory $searchResultsFactory,
        private CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @param OfferInterface $offer
     * @return OfferInterface
     * @throws CouldNotSaveException
     */
    public function save(OfferInterface $offer): OfferInterface
    {
        try {
            $this->resource->save($offer);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the offer: %1', $exception->getMessage()),
                $exception
            );
        }
        return $offer;
    }

    /**
     * @inheritDoc
     * Load offer with ALL related data
     * @throws NoSuchEntityException
     */
    public function getById(int $offerId): OfferInterface
    {
        $offer = $this->offerFactory->create();
        $this->resource->load($offer, $offerId);

        if (!$offer->getEntityId()) {
            throw new NoSuchEntityException(__('Offer with id "%1" does not exist.', $offerId));
        }

        // Load all related entities
        $this->loadRelatedEntities($offer);

        return $offer;
    }

    /**
     * Load offer by offer number with all related data
     *
     * @param string $offerNumber
     * @return OfferInterface
     * @throws NoSuchEntityException
     */
    public function getByOfferNumber(string $offerNumber): OfferInterface
    {
        $offer = $this->offerFactory->create();
        $this->resource->load($offer, $offerNumber, OfferInterface::OFFER_NUMBER);

        if (!$offer->getEntityId()) {
            throw new NoSuchEntityException(__('Offer with number "%1" does not exist.', $offerNumber));
        }

        // Load all related entities
        $this->loadRelatedEntities($offer);

        return $offer;
    }

    /**
     * Load all related entities for offer
     *
     * @param OfferInterface $offer
     * @return void
     */
    private function loadRelatedEntities(OfferInterface $offer): void
    {
        $offerId = $offer->getEntityId();

        // Load items with their attachments
        $itemsCollection = $this->itemCollectionFactory->create();
        $itemsCollection->addFieldToFilter('offer_id', $offerId)
            ->setOrder('item_id', 'ASC');

        $items = [];
       /* foreach ($itemsCollection->getItems() as $item) {
            // Load attachments for each item
            $itemAttachmentsCollection = $this->createItemAttachmentsCollection($item->getItemId());
            $item->setData('attachments', $itemAttachmentsCollection->getItems());
            $items[] = $item;
        }*/
        $offer->setData('items', $items);

        // Load history
        $historyCollection = $this->historyCollectionFactory->create();
        $historyCollection->addFieldToFilter('offer_id', $offerId)
            ->setOrder('created_at', 'DESC');
        $offer->setData('history', $historyCollection->getItems());

        // Load offer attachments
        $attachmentsCollection = $this->attachmentCollectionFactory->create();
        $attachmentsCollection->addFieldToFilter('offer_id', $offerId)
            ->setOrder('sort_order', 'ASC');
        $offer->setData('attachments', $attachmentsCollection->getItems());
    }


    /**
     * Create item attachments collection for specific item
     *
     * @param int $itemId
     * @return \OneMoveTwo\Offers\Model\ResourceModel\OfferItemAttachment\Collection
     */
    private function createItemAttachmentsCollection(int $itemId)
    {
        // Здесь нужно будет создать фабрику для OfferItemAttachment коллекции
        // Пока заглушка - в реальности нужно инжектить OfferItemAttachmentCollectionFactory
        return new \Magento\Framework\Data\Collection();
    }

    public function getByQuoteId(string $quoteId): OfferInterface
    {
        $offer = $this->offerFactory->create();
        $this->resource->load($offer, $quoteId, OfferInterface::QUOTE_ID);
        if (!$offer->getEntityId()) {
            $offer = $this->offerFactory->create();
        }
        return $offer;
    }

    /**
     * @param SearchCriteriaInterface $criteria
     * @return OfferSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): OfferSearchResultsInterface
    {
        $collection = $this->offerCollectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * @param OfferInterface $offer
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(OfferInterface $offer): bool
    {
        try {
            $this->resource->delete($offer);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @param int $id
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    /**
     * Load only basic related data for lists (to avoid performance issues)
     *
     * @param OfferInterface $offer
     * @return void
     */
    private function loadBasicRelatedData(OfferInterface $offer): void
    {
        $offerId = $offer->getEntityId();

        // Load only items count and basic info for lists
        $itemsCollection = $this->itemCollectionFactory->create();
        $itemsCollection->addFieldToFilter('offer_id', $offerId);
        $offer->setData('items_count_actual', $itemsCollection->getSize());

        // Load latest status from history
        $historyCollection = $this->historyCollectionFactory->create();
        $historyCollection->addFieldToFilter('offer_id', $offerId)
            ->setOrder('created_at', 'DESC')
            ->setPageSize(1);
        $latestHistory = $historyCollection->getFirstItem();
        if ($latestHistory->getId()) {
            $offer->setData('latest_history', $latestHistory);
        }
    }

    /**
     * Get offer with full data loading
     *
     * @param int $offerId
     * @return OfferInterface
     * @throws NoSuchEntityException
     */
    public function getByIdWithFullData(int $offerId): OfferInterface
    {
        return $this->getById($offerId); // Already loads full data
    }

    /**
     * Get list with full data loading (use with caution for performance)
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getListWithFullData(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->offerCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        // Load full related data for each offer
        foreach ($collection->getItems() as $offer) {
            $this->loadRelatedEntities($offer);
        }

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }
}
