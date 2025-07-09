<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\ResourceModel\OfferItemAttachment;

use OneMovetwo\Offers\Model\Data\OfferItemAttachment;
use OneMovetwo\Offers\Model\ResourceModel\OfferItemAttachment as OfferItemAttachmentResource;
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
        $this->_init(OfferItemAttachment::class, OfferItemAttachmentResource::class);
    }
}
