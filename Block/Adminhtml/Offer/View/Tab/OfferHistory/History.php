<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Tab\OfferHistory;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

class History extends Template
{
    public function __construct(
        private readonly Registry $registry,
        Context $context,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;

        parent::__construct($context, $data);
    }

    /**
     * Preparing global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $onclick = "submitAndReloadArea($('order_history_block').parentNode, '" . $this->getSubmitUrl() . "')";
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            ['label' => __('Submit Comment'), 'class' => 'action-save action-secondary', 'onclick' => $onclick]
        );
        $this->setChild('submit_button', $button);
        return parent::_prepareLayout();
    }

    /**
     * Get stat uses
     *
     * @return array
     */
    public function getStatuses(): array
    {
        return [
                'New'=> 'New',
                'Done'=>'Done'
        ];
    }

    /**
     * Check allow to send order comment email
     *
     * @return bool
     */
    public function canSendCommentEmail(): bool
    {
        return false;
    }

    /**
     * Retrieve order model
     *
     */
    public function getOffer()
    {
        return $this->registry->registry('current_offer');
    }

    /**
     * Check allow to add comment
     *
     * @return bool
     */
    public function canAddComment()
    {
        return true;
    }

    /**
     * Submit URL getter
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('offers/*/addComment', ['offer_id' => $this->getOffer()->getId()]);
    }

    /**
     * Customer Notification Applicable check method
     *
     * @param  \Magento\Sales\Model\Order\Status\History $history
     * @return bool
     */
    public function isCustomerNotificationNotApplicable()
    {
        return false;
    }
}
