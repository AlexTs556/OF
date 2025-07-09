<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Search\Grid\Renderer;

class Product extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    /**
     * Render product name to add Configure link
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row): string
    {
        $rendered = parent::render($row);
        $isConfigurable = $row->canConfigure();
        $style = $isConfigurable ? '' : 'disabled';
        if ($isConfigurable) {
            $prodAttributes = sprintf(
                'list_type = "product_to_add" product_id = %s',
                $row->getId()
            );
        } else {
            $prodAttributes = 'disabled="disabled"';
        }

        $javascript = sprintf(
            '<a href="javascript:void(0)" class="action-configure %s" %s>%s</a>',
            $style,
            $prodAttributes,
            __('Configure')
        );

        return $javascript . $rendered;
    }
}
