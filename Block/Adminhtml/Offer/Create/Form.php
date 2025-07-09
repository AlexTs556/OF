<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\Create;

class Form extends \Magento\Sales\Block\Adminhtml\Order\Create\Form
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        parent::_construct();
        $this->setId('sales_order_create_form');
    }

    /**
     * @return string
     */
    public function getLoadBlockUrl(): string
    {
        return $this->getUrl('offers/offer_create/loadBlock');
    }

    /**
     * @return string
     */
    public function getSaveUrl(): string
    {
        return $this->getUrl('offers/offer/save');
    }
}
