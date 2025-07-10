<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use OneMoveTwo\Offers\Api\Data\OfferItemInterface;
use OneMoveTwo\Offers\Api\Data\OfferItemInterfaceFactory;
use OneMoveTwo\Offers\Api\Data\OfferItemSearchResultsInterface;
use OneMoveTwo\Offers\Api\Data\OfferItemSearchResultsInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferItemRepositoryInterface;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItem as OfferItemResource;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItem\CollectionFactory;

readonly class OfferItemRepository implements OfferItemRepositoryInterface
{
    /**
     * @param OfferItemResource $offerItemResource
     * @param OfferItemInterfaceFactory $offerItemFactory
     * @param CollectionFactory $offerItemCollectionFactory
     * @param OfferItemSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        private OfferItemResource                      $offerItemResource,
        private OfferItemInterfaceFactory              $offerItemFactory,
        private CollectionFactory                      $offerItemCollectionFactory,
        private OfferItemSearchResultsInterfaceFactory $searchResultsFactory,
        private CollectionProcessorInterface           $collectionProcessor
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(OfferItemInterface $offerItem): OfferItemInterface
    {
        try {
            $this->offerItemResource->save($offerItem);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the offer item: %1', $exception->getMessage())
            );
        }
        return $offerItem;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $itemId): OfferItemInterface
    {
        $offerItem = $this->offerItemFactory->create();
        $this->offerItemResource->load($offerItem, $itemId);

        if (!$offerItem->getItemId()) {
            throw new NoSuchEntityException(__('Offer item with id "%1" does not exist.', $itemId));
        }

        return $offerItem;
    }

    /**
     * @inheritdoc
     */
    public function getByOfferId(int $offerId): array
    {
        $collection = $this->offerItemCollectionFactory->create();
        $collection->addOfferFilter($offerId);

        return $collection->getItems();
    }

    /**
     * @inheritdoc
     */
    public function getByProductId(int $productId): array
    {
        $collection = $this->offerItemCollectionFactory->create();
        $collection->addProductFilter($productId);

        return $collection->getItems();
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): OfferItemSearchResultsInterface
    {
        $collection = $this->offerItemCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(OfferItemInterface $offerItem): bool
    {
        try {
            $this->offerItemResource->delete($offerItem);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the offer item: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $itemId): bool
    {
        return $this->delete($this->getById($itemId));
    }

    /**
     * @inheritdoc
     */
    public function deleteByOfferId(int $offerId): bool
    {
        try {
            $connection = $this->offerItemResource->getConnection();
            $tableName = $this->offerItemResource->getMainTable();

            $connection->delete($tableName, ['offer_id = ?' => $offerId]);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete offer items: %1', $exception->getMessage())
            );
        }

        return true;
    }
}
