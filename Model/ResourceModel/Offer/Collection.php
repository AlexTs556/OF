<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\ResourceModel\Offer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use OneMoveTwo\Offers\Model\Data\Offer;
use OneMoveTwo\Offers\Model\ResourceModel\Offer as OfferResource;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItem\CollectionFactory as OfferItemCollectionFactory;
use OneMoveTwo\Offers\Model\ResourceModel\OfferHistory\CollectionFactory as OfferHistoryCollectionFactory;
use OneMoveTwo\Offers\Model\ResourceModel\OfferAttachment\CollectionFactory as OfferAttachmentCollectionFactory;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Collection extends AbstractCollection
{
    /**
     * ID field name
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    private bool $loadRelatedData = false;

    public function __construct(
        private readonly OfferItemCollectionFactory $itemCollectionFactory,
        private readonly OfferHistoryCollectionFactory $historyCollectionFactory,
        private readonly OfferAttachmentCollectionFactory $attachmentCollectionFactory,
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(Offer::class, OfferResource::class);
    }

    /**
     * Enable loading of related data
     *
     * @param bool $flag
     * @return $this
     */
    public function setLoadRelatedData(bool $flag = true): self
    {
        $this->loadRelatedData = $flag;
        return $this;
    }

    /**
     * Add items count to select
     *
     * @return $this
     */
    public function addItemsCount(): self
    {
        $this->getSelect()->joinLeft(
            ['items_count' => $this->getTable('onemovetwo_offers_item')],
            'main_table.entity_id = items_count.offer_id',
            ['items_count_actual' => 'COUNT(items_count.item_id)']
        )->group('main_table.entity_id');

        return $this;
    }

    /**
     * Add latest status from history
     *
     * @return $this
     */
    public function addLatestStatus(): self
    {
        $connection = $this->getConnection();

        // Subquery to get latest history record for each offer
        $latestHistorySubquery = $connection->select()
            ->from(
                ['h1' => $this->getTable('onemovetwo_offers_history')],
                ['offer_id', 'status', 'created_at', 'comment']
            )
            ->joinInner(
                ['h2' => $this->getTable('onemovetwo_offers_history')],
                'h1.offer_id = h2.offer_id',
                []
            )
            ->group('h1.offer_id')
            ->having('h1.created_at = MAX(h2.created_at)');

        $this->getSelect()->joinLeft(
            ['latest_history' => new \Zend_Db_Expr('(' . $latestHistorySubquery . ')')],
            'main_table.entity_id = latest_history.offer_id',
            [
                'latest_status' => 'latest_history.status',
                'latest_status_date' => 'latest_history.created_at',
                'latest_comment' => 'latest_history.comment'
            ]
        );

        return $this;
    }

    /**
     * Add attachments count
     *
     * @return $this
     */
    public function addAttachmentsCount(): self
    {
        $this->getSelect()->joinLeft(
            ['attachments_count' => $this->getTable('onemovetwo_offers_attachments')],
            'main_table.entity_id = attachments_count.offer_id',
            ['attachments_count' => 'COUNT(attachments_count.attachment_id)']
        )->group('main_table.entity_id');

        return $this;
    }

    /**
     * Add all summary data (counts, latest status)
     *
     * @return $this
     */
    public function addSummaryData(): self
    {
        return $this->addItemsCount()
            ->addLatestStatus()
            ->addAttachmentsCount();
    }

    /**
     * After load processing - load related data if enabled
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        if ($this->loadRelatedData) {
            $this->loadRelatedDataForItems();
        }

        return $this;
    }

    /**
     * Load related data for all items in collection
     *
     * @return void
     */
    private function loadRelatedDataForItems(): void
    {
        $offerIds = $this->getColumnValues('entity_id');

        if (empty($offerIds)) {
            return;
        }

        // Load all items for all offers at once
        $itemsCollection = $this->itemCollectionFactory->create();
        $itemsCollection->addFieldToFilter('offer_id', ['in' => $offerIds])
            ->setOrder('item_id', 'ASC');

        // Group items by offer_id
        $itemsByOfferId = [];
        foreach ($itemsCollection->getItems() as $item) {
            $itemsByOfferId[$item->getOfferId()][] = $item;
        }

        // Load all history for all offers at once
        $historyCollection = $this->historyCollectionFactory->create();
        $historyCollection->addFieldToFilter('offer_id', ['in' => $offerIds])
            ->setOrder('created_at', 'DESC');

        // Group history by offer_id
        $historyByOfferId = [];
        foreach ($historyCollection->getItems() as $history) {
            $historyByOfferId[$history->getOfferId()][] = $history;
        }

        // Load all attachments for all offers at once
        $attachmentsCollection = $this->attachmentCollectionFactory->create();
        $attachmentsCollection->addFieldToFilter('offer_id', ['in' => $offerIds])
            ->setOrder('sort_order', 'ASC');

        // Group attachments by offer_id
        $attachmentsByOfferId = [];
        foreach ($attachmentsCollection->getItems() as $attachment) {
            $attachmentsByOfferId[$attachment->getOfferId()][] = $attachment;
        }

        // Assign related data to each offer
        foreach ($this->getItems() as $offer) {
            $offerId = $offer->getEntityId();

            // Set items
            $offer->setData('items', $itemsByOfferId[$offerId] ?? []);

            // Set history
            $offer->setData('history', $historyByOfferId[$offerId] ?? []);

            // Set attachments
            $offer->setData('attachments', $attachmentsByOfferId[$offerId] ?? []);
        }
    }

    /**
     * Filter by status
     *
     * @param string|array $status
     * @return $this
     */
    public function addStatusFilter($status): self
    {
        if (is_array($status)) {
            $this->addFieldToFilter('status', ['in' => $status]);
        } else {
            $this->addFieldToFilter('status', $status);
        }
        return $this;
    }

    /**
     * Filter by customer
     *
     * @param int $customerId
     * @return $this
     */
    public function addCustomerFilter(int $customerId): self
    {
        $this->addFieldToFilter('customer_id', $customerId);
        return $this;
    }

    /**
     * Filter by date range
     *
     * @param string $from
     * @param string $to
     * @return $this
     */
    public function addDateRangeFilter(string $from, string $to): self
    {
        $this->addFieldToFilter('created_at', ['from' => $from, 'to' => $to]);
        return $this;
    }

    /**
     * Add order by created date
     *
     * @param string $direction
     * @return $this
     */
    public function addCreatedAtOrder(string $direction = 'DESC'): self
    {
        $this->setOrder('created_at', $direction);
        return $this;
    }
}
