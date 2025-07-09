<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use OneMoveTwo\Offers\Model\Data\Offer;

class OfferSummary extends Template implements TabInterface
{

    public function __construct(
        private readonly Registry $registry,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getTabLabel(): \Magento\Framework\Phrase|string
    {
        return __('Offer Summary');
    }
    /**
     * Get Tab title
     */
    public function getTabTitle(): \Magento\Framework\Phrase|string
    {
        return __('Offer Summary');
    }

    /**
     * Check if tab can be shown
     */
    public function canShowTab(): true
    {
        return true;
    }

    /**
     * Retrieve offer model instance
     */
    public function getOffer(): Offer
    {
        return $this->registry->registry('current_offer');
    }

    /**
     * Check if tab is hidden
     */
    public function isHidden(): false
    {
        return false;
    }
}
