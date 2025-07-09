<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View;

use Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate;
use Magento\Framework\Phrase;

class Search extends AbstractCreate
{

    /**
     * Contains button descriptions to be shown at the top of quote view
     */
    protected array $buttons = [];

    /**
     * Get header text
     */
    public function getHeaderText(): Phrase
    {
        return __('Please select products');
    }

    /**
     * Add button to the items header
     */
    public function addButton(array $args): void
    {
        $this->buttons[] = $args;
    }

    /**
     * Get buttons html
     */
    public function getButtonsHtml(): string
    {
        $html = '';
        foreach ($this->buttons as $buttonData) {
            $html .= $this->getLayout()
                ->createBlock(\Magento\Backend\Block\Widget\Button::class)
                ->setData($buttonData)
                ->toHtml();
        }

        return $html;
    }

    /**
     * Get header css class
     */
    public function getHeaderCssClass(): string
    {
        return 'head-catalog-product';
    }

    /**
     * Constructor
     */
    protected function _construct(): void
    {
        parent::_construct();

        $this->setId('offer_view_search');
        $this->addButton(
            [
                'label' => __('Add Selected Product(s) to Offer'),
                'class' => 'action-add action-secondary',
                'onclick' => 'offer.productGridAddSelected()'
            ]
        );
        $this->addButton(
            [
                'label' => __('Cancel'),
                'class' => 'action-cancel action-secondary',
                'onclick' => 'offer.closeProductSearchGrid()'
            ]
        );
    }
}
