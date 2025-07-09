<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View;


use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Registry;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    public function __construct(
        private readonly Registry $registry,
        Context $context,
        EncoderInterface $jsonEncoder,
        Session $authSession,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    /**
     * @throws LocalizedException
     */
    public function getQuote()
    {
        if ($this->registry->registry('current_offer')) {
            return $this->registry->registry('current_offer');
        }

        throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t get the offer instance right now.'));
    }

    protected function _construct(): void
    {
        parent::_construct();
        $this->setId('offers_offer_view_tabs');
        $this->setDestElementId('offers_offer_view');
        $this->setTitle(__('Offer View'));
    }
}
