<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\ResourceModel\OfferAttachment;

use OneMoveTwo\Offers\Model\Data\OfferAttachment;
use OneMoveTwo\Offers\Model\ResourceModel\OfferAttachment as OfferAttachmentResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'attachment_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OfferAttachment::class, OfferAttachmentResource::class);
    }
}
