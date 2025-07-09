<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template;

class ShippingInfo extends Template implements TabInterface
{

    public function getTabLabel(): \Magento\Framework\Phrase|string
    {
        return __('Shipping Information');
    }
    /**
     * Get Tab title
     */
    public function getTabTitle(): \Magento\Framework\Phrase|string
    {
        return __('Shipping Information');
    }

    /**
     * Check if tab can be shown
     */
    public function canShowTab(): true
    {
        return true;
    }

    /**
     * Check if tab is hidden
     */
    public function isHidden(): false
    {
        return false;
    }
}
