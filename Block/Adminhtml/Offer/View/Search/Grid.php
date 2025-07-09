<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Search;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Config;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Config as SalesConfig;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Store\Model\Store;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var string
     */
    protected $_template = 'OneMoveTwo_Offers::widget/grid/product.phtml';

    public function __construct(
        private readonly ProductFactory $productFactory,
        private readonly Config $catalogConfig,
        private readonly Quote $sessionQuote,
        private readonly SalesConfig $salesConfig,
        Context $context,
        Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Retrieve quote object
     */
    public function getQuote(): \Magento\Quote\Model\Quote
    {
        return $this->sessionQuote->getQuote();
    }

    /**
     * Get grid url
     */
    public function getGridUrl(): string
    {
        return $this->getUrl(
            'offers/offer_view/loadBlock',
            ['block' => 'search_grid', '_current' => true, 'collapse' => null]
        );
    }

    /**
     * Constructor
     *
     * @throws FileSystemException
     */
    protected function _construct(): void
    {
        parent::_construct();
        $this->setId('offers_offer_view_search_grid');
        $this->setRowClickCallback('offer.productGridRowClick.bind(offer)');
        $this->setCheckboxCheckCallback('offer.productGridCheckboxCheck.bind(offer)');
        $this->setRowInitCallback('offer.productGridRowInit.bind(offer)');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Add column filter to collection
     *
     * @param Column $column
     * @return $this
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column): static
    {
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }

            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } elseif ($productIds) {
                $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * Get selected products
     */
    protected function _getSelectedProducts(): mixed
    {
        return $this->getRequest()->getPost('products', []);
    }

    /**
     * Prepare collection to be displayed in the grid
     */
    protected function _prepareCollection(): static
    {
        $attributes = $this->catalogConfig->getProductAttributes();
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productFactory->create()->getCollection();
        $collection->setStore(
            $this->getStore()
        )->addAttributeToSelect(
            $attributes
        )->addAttributeToSelect(
            'sku'
        )->addStoreFilter()->addAttributeToFilter(
            'type_id',
            $this->salesConfig->getAvailableProductTypes()
        )->addAttributeToSelect(
            'gift_message_available'
        );

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Retrieve quote store object
     */
    public function getStore(): Store
    {
        return $this->sessionQuote->getStore();
    }

    /**
     * Prepare columns
     */
    protected function _prepareColumns(): static
    {
        $this->addColumn(
            'image',
            [
                'header' => __('Image'),
                'align' => 'center',
                'filter' => false,
                'sortable' => false,
                'renderer' => '\OneMoveTwo\Offers\Block\Adminhtml\Offer\Grid\Renderer\ThumbnailRenderer'
            ]
        );
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'index' => 'entity_id'
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Product'),
                'renderer' => \OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Search\Grid\Renderer\Product::class,
                'index' => 'name'
            ]
        );
        $this->addColumn('sku', ['header' => __('SKU'), 'index' => 'sku']);
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'column_css_class' => 'price',
                'type' => 'currency',
                'currency_code' => $this->getStore()->getCurrentCurrencyCode(),
                'rate' => $this->getStore()->getBaseCurrency()->getRate($this->getStore()->getCurrentCurrencyCode()),
                'index' => 'price',
                'renderer' => \OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Search\Grid\Renderer\Price::class
            ]
        );

        $this->addColumn(
            'in_products',
            [
                'header' => __('Select'),
                'type' => 'checkbox',
                'name' => 'in_products',
                'values' => $this->_getSelectedProducts(),
                'index' => 'entity_id',
                'sortable' => false,
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'qty',
            [
                'filter' => false,
                'sortable' => false,
                'header' => __('Quantity'),
                'renderer' => \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Qty::class,
                'name' => 'qty',
                'inline_css' => 'qty',
                'type' => 'input',
                'validate_class' => 'validate-number',
                'index' => 'qty'
            ]
        );

        /*$this->addColumn(
            'is_unique',
            [
                'header' => __('Unique Item'),
                'type' => 'checkbox',
                'name' => 'is_unique',
                'values' => '1',
                'index' => 'is_unique',
                'filter' => false,
                'sortable' => false,
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );*/

        return parent::_prepareColumns();
    }

    /**
     * Add custom options to product collection
     */
    protected function _afterLoadCollection(): static
    {
        $this->getCollection()->addOptionsToResult();
        return parent::_afterLoadCollection();
    }
}
