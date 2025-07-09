<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\ResourceModel\OfferHistory;

use OneMoveTwo\Offers\Model\Data\OfferHistory;
use OneMoveTwo\Offers\Model\ResourceModel\OfferHistory as OfferHistoryResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'history_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OfferHistory::class, OfferHistoryResource::class);
    }
}
