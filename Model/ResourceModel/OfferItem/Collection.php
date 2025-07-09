<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\ResourceModel\OfferItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use OneMoveTwo\Offers\Model\Data\OfferItem;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItem as OfferItemResource;

class Collection extends AbstractCollection
{
    /**
     * ID field name
     *
     * @var string
     */
    protected $_idFieldName = 'item_id';

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(OfferItem::class, OfferItemResource::class);
    }
}
