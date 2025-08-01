<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OfferHistory extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('onemovetwo_offers_history', 'history_id');
    }
}
