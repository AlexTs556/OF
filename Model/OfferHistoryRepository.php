<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model;

use OneMoveTwo\Offers\Api\Data\OfferHistoryInterface;
use OneMoveTwo\Offers\Api\Data\OfferHistoryInterfaceFactory;
use OneMoveTwo\Offers\Api\Data\OfferHistorySearchResultsInterface;
use OneMoveTwo\Offers\Api\Data\OfferHistorySearchResultsInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferHistoryRepositoryInterface;
use OneMoveTwo\Offers\Model\ResourceModel\OfferHistory as OfferHistoryResource;
use OneMoveTwo\Offers\Model\ResourceModel\OfferHistory\Collection;
use OneMoveTwo\Offers\Model\ResourceModel\OfferHistory\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

readonly class OfferHistoryRepository implements OfferHistoryRepositoryInterface
{
    /**
     * @param OfferHistoryResource $resource
     * @param OfferHistoryInterfaceFactory $offerHistoryFactory
     * @param CollectionFactory $collectionFactory
     * @param OfferHistorySearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        private OfferHistoryResource                      $resource,
        private OfferHistoryInterfaceFactory              $offerHistoryFactory,
        private CollectionFactory                         $collectionFactory,
        private OfferHistorySearchResultsInterfaceFactory $searchResultsFactory,
        private CollectionProcessorInterface              $collectionProcessor
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(OfferHistoryInterface $offerHistory): OfferHistoryInterface
    {
        try {
            $this->resource->save($offerHistory);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the offer history: %1', $exception->getMessage()),
                $exception
            );
        }
        return $offerHistory;
    }

    /**
     * @inheritDoc
     */
    public function get(int $historyId): OfferHistoryInterface
    {
        $offerHistory = $this->offerHistoryFactory->create();
        $this->resource->load($offerHistory, $historyId);
        if (!$offerHistory->getHistoryId()) {
            throw new NoSuchEntityException(__('Offer history with id "%1" does not exist.', $historyId));
        }
        return $offerHistory;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): OfferHistorySearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var OfferHistorySearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(OfferHistoryInterface $offerHistory): bool
    {
        try {
            $this->resource->delete($offerHistory);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the offer history: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $historyId): bool
    {
        return $this->delete($this->get($historyId));
    }

    /**
     * @inheritDoc
     */
    public function getByOfferId(int $offerId): array
    {
        return [];
    }
}
