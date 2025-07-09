<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OfferAttachment extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('onemovetwo_offers_attachments', 'attachment_id');
    }
}
