<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItemAttachment\CollectionFactory as OfferItemAttachmentCollectionFactory;


class OfferItem extends AbstractDb
{

    public function __construct(
        private readonly OfferItemAttachmentCollectionFactory $attachmentCollectionFactory,
        Context $context,
        $connectionName = null
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
        $this->_init('onemovetwo_offers_item', 'item_id');
    }

    /**
     * Load object with attachments
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param mixed $value
     * @param string|null $field
     * @return $this
     */
    public function load(\Magento\Framework\Model\AbstractModel $object, $value, $field = null)
    {
        // Load main object
        parent::load($object, $value, $field);

        // Load attachments if object was loaded successfully
        if ($object->getId()) {
            $this->loadAttachments($object);
        }

        return $this;
    }

    /**
     * Load attachments for offer item
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return void
     */
    private function loadAttachments(\Magento\Framework\Model\AbstractModel $object): void
    {
        $itemId = $object->getId();

        // Load attachments
        $attachmentsCollection = $this->attachmentCollectionFactory->create();
        $attachmentsCollection->addFieldToFilter('offer_items_id', $itemId)
            ->setOrder('sort_order', 'ASC');
        $object->setData('attachments', $attachmentsCollection->getItems());
    }

    /**
     * Load multiple items with their attachments by offer ID
     *
     * @param int $offerId
     * @return array
     */
    public function loadItemsWithAttachmentsByOfferId(int $offerId): array
    {
        $connection = $this->getConnection();

        // Get all items for the offer
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('offer_id = ?', $offerId)
            ->order('item_id ASC');

        $itemsData = $connection->fetchAll($select);
        $items = [];

        foreach ($itemsData as $itemData) {
            // Create item object
            $item = new \Magento\Framework\DataObject($itemData);

            // Load attachments for this item
            $attachmentsCollection = $this->attachmentCollectionFactory->create();
            $attachmentsCollection->addFieldToFilter('offer_items_id', $itemData['item_id'])
                ->setOrder('sort_order', 'ASC');
            $item->setData('attachments', $attachmentsCollection->getItems());

            $items[] = $item;
        }

        return $items;
    }
}
