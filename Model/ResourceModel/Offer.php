<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\SalesSequence\Model\Manager;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItem\CollectionFactory as OfferItemCollectionFactory;
use OneMoveTwo\Offers\Model\ResourceModel\OfferHistory\CollectionFactory as OfferHistoryCollectionFactory;
use OneMoveTwo\Offers\Model\ResourceModel\OfferAttachment\CollectionFactory as OfferAttachmentCollectionFactory;


class Offer extends AbstractDb
{

    public function __construct(
        private readonly Manager $sequenceManager,
        private readonly OfferItemCollectionFactory $itemCollectionFactory,
        private readonly OfferHistoryCollectionFactory $historyCollectionFactory,
        private readonly OfferAttachmentCollectionFactory $attachmentCollectionFactory,
        Context $context,
        $connectionName = null,
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('onemovetwo_offers', 'entity_id');
    }

    /**
     * Load object with related data
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param mixed $value
     * @param string|null $field
     * @return $this
     */
    public function load(\Magento\Framework\Model\AbstractModel $object, $value, $field = null): static
    {
        // Load main object
        parent::load($object, $value, $field);

        // Load related data if object was loaded successfully
        if ($object->getId()) {
            $this->loadRelatedData($object);
        }

        return $this;
    }

    /**
     * Load all related data for offer
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return void
     */
    private function loadRelatedData(\Magento\Framework\Model\AbstractModel $object): void
    {
        $offerId = $object->getId();

        // Load items
        $itemsCollection = $this->itemCollectionFactory->create();
        $itemsCollection->addFieldToFilter('offer_id', $offerId)
            ->setOrder('item_id', 'ASC');
        $object->setData('items', $itemsCollection->getItems());

        // Load history
        $historyCollection = $this->historyCollectionFactory->create();
        $historyCollection->addFieldToFilter('offer_id', $offerId)
            ->setOrder('created_at', 'DESC');
        $object->setData('history', $historyCollection->getItems());

        // Load attachments
        $attachmentsCollection = $this->attachmentCollectionFactory->create();
        $attachmentsCollection->addFieldToFilter('offer_id', $offerId)
            ->setOrder('sort_order', 'ASC');
        $object->setData('attachments', $attachmentsCollection->getItems());
    }

    /**
     * Load offer with all related data by offer number
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param string $offerNumber
     * @return $this
     */
    public function loadByOfferNumber(\Magento\Framework\Model\AbstractModel $object, string $offerNumber)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('offer_number = ?', $offerNumber);

        $data = $connection->fetchRow($select);
        if ($data) {
            $object->setData($data);
            $this->loadRelatedData($object);
        }

        return $this;
    }

    protected function _beforeSave($object): Offer|static
    {
        if ($object->getOfferNumber() == null) {
            $newIncrementId = $this->sequenceManager->getSequence(
                'order',
                $object->getStoreId()
            )->getNextValue();

            $object->setOfferNumber($newIncrementId);
        }
        parent::_beforeSave($object);

        return $this;
    }
}
