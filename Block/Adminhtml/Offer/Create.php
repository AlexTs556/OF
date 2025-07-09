<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer;

use OneMoveTwo\Offers\Block\Adminhtml\Offer\Create\Header;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\Block\Widget\Form\Container;

class Create extends \Magento\Sales\Block\Adminhtml\Order\Create
{
    /**
     * Create constructor
     *
     * @return void
     */
    protected function _construct(): void
    {
        parent::_construct();

        //update save button
        $offerId = $this->_getSession()->getOfferId();
        $buttonText = $offerId ? 'Update Offer' : '@@Create Offer!';
        $this->buttonList->update('save', 'label', __($buttonText));

        //update back button
        $this->buttonList->update(
            'back',
            'onclick',
            "setLocation('" . $this->getBackUrl() . "')"
        );

        //update cancel button
        $confirm = __('Are you sure you want to cancel this Offer?');

        $this->buttonList->update(
            'reset',
            'onclick',
            "deleteConfirm('$confirm','" . $this->getBackUrl() . "')"
        );
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getHeaderHtml(): string
    {
        return sprintf(
            '<div id="order-header">%s</div>',
            $this->getLayout()->createBlock(Header::class)->toHtml()
        );
    }

    /**
     * @return Container
     * @throws LocalizedException
     */
    protected function _prepareLayout(): Container
    {
        $pageTitle = $this->getLayout()->createBlock(Header::class)->toHtml();
        if (is_object($this->getLayout()->getBlock('page.title'))) {
            $this->getLayout()->getBlock('page.title')->setPageTitle($pageTitle);
        }

        return Container::_prepareLayout();
    }

    /**
     * Get URL for back and cancel button
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl('offers/offer/');
    }
}
