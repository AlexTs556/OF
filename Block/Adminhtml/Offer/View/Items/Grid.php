<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Items;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Catalog\Helper\Image;
use Magento\Tax\Block\Item\Price\Renderer;

class Grid extends \Magento\Sales\Block\Adminhtml\Order\Create\Items\Grid
{
    const ALTERNATIVE_COST_FIELD = 'price';

    /**
     * @var \Cart2Quote\Quotation\Model\Quote
     */
    protected $quoteCreate;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Catalog\Helper\Product\Configuration
     */
    protected $productConfig;


    public function __construct(
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Cart2Quote\Quotation\Model\Quote $quoteCreate,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\GiftMessage\Model\Save $giftMessageSave,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\GiftMessage\Helper\Message $messageHelper,
        StockRegistryInterface $stockRegistry,
        StockStateInterface $stockState,
        \Magento\Framework\Registry $registry,

        private readonly OfferRepositoryInterface $offerRepository,
        private readonly Renderer $itemPriceRenderer,
        private readonly Image $imageHelper,
        private readonly QuoteRepository $quoteRepository,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->quoteCreate = $quoteCreate;
        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $wishlistFactory,
            $giftMessageSave,
            $taxConfig,
            $taxData,
            $messageHelper,
            $stockRegistry,
            $stockState,
            $data
        );

        $this->productConfig = $productConfig;
    }

    /**
     * Retrieve create quote model object
     *
     * @return \Cart2Quote\Quotation\Model\Quote
     */
    public function getCreateOrderModel()
    {
        return $this->quoteCreate;
    }

    /**
     * Get order item extra info block
     *
     * @param Item $item
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    public function getItemExtraInfo($item)
    {
        return $this->getLayout()->getBlock('quote_item_extra_info')->setItem($item);
    }

    /**
     * Accept option value and return its formatted view
     *
     * @param string|array $optionValue
     * @return array
     */
    public function getFormatedOptionValue($optionValue)
    {
        $params = [
            'max_length' => 55,
            'cut_replacer' => ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>'
        ];

        return $this->productConfig->getFormattedOptionValue($optionValue, $params);
    }

    /**
     * Get original subtotal
     *
     * @return float
     */
    public function getOriginalSubtotal()
    {
        return $this->getQuote()->getOriginalSubtotal();
    }

    /**
     * Retrieve quote model
     */
    public function getQuote()
    {
        if (!$quote = $this->coreRegistry->registry('current_quote')) {
            $offer = $this->coreRegistry->registry('current_offer');
            if (!$offer->getId() || !$offer->getQuoteId()) {
                $offer = $this->offerRepository->getById($this->getRequest()->getParam('entity_id'));
            }

            $quote = $this->quoteRepository->get($offer->getQuoteId());


            $this->coreRegistry->register(
                'current_quote',
                $quote
            );
        }

        return $quote;
    }

    /**
     * Get proposal total
     *
     * @deprecated Please use the total collected on the quote.
     * @see \Cart2Quote\Quotation\Block\Quote\Totals
     * @return float
     */
    public function getProposalTotal()
    {
        $proposalTotal = 0;
        foreach ($this->getQuote()->getAllVisibleItems() as $item) {
            $itemCustomPrice = $item->getCustomPrice();
            $itemCustomPrice = $itemCustomPrice ?? $item->getPrice();
            $proposalTotal += $itemCustomPrice * $item->getQty();
        }

        return $proposalTotal;
    }

    /**
     * Get cost total
     *
     * @param bool|true $useAlternativeCostField
     * @return float
     */
    public function getCostTotal($useAlternativeCostField = true)
    {
        $totalCost = 0;
        foreach ($this->getQuote()->getAllVisibleItems() as $item) {
            $itemCost = $this->getItemCost($item, $useAlternativeCostField);
            $totalCost += $itemCost * $item->getQty();
        }

        return $totalCost;
    }

    /**
     * Get item cost
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param bool|true $useAlternativeCostField
     * @return float
     */
    public function getItemCost(\Magento\Quote\Model\Quote\Item $item, $useAlternativeCostField = true)
    {
        $itemCost = $item->getCost();
        if (!$itemCost) {
            $itemCost = $item->getBaseCost();
        }

        if (!$itemCost && $useAlternativeCostField) {
            $itemCost = $item->getData(self::ALTERNATIVE_COST_FIELD);
        }

        return $itemCost;
    }

    /**
     * Get total item qty
     *
     * @return int
     */
    public function getTotalItemQty()
    {
        $itemsQty = 0;
        foreach ($this->getQuote()->getAllVisibleItems() as $item) {
            $itemQty = $item->getQty();
            $itemsQty += $itemQty;
        }

        return $itemsQty;
    }



    /**
     * Check action active status
     *
     * @return bool
     */
    public function isActiveAction()
    {
        $availableStatus = [
            \Cart2Quote\Quotation\Model\Quote\Status::STATUS_ACCEPTED,
            \Cart2Quote\Quotation\Model\Quote\Status::STATUS_ORDERED,
            \Cart2Quote\Quotation\Model\Quote\Status::STATUS_CLOSED
        ];

        $quote = $this->getQuote();
        if (!in_array($quote->getStatus(), $availableStatus)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check make optional active status
     *
     * @return bool
     */
    public function isActiveMakeOptional()
    {
        $availableStatus = [
            \Cart2Quote\Quotation\Model\Quote\Status::STATUS_NEW,
            \Cart2Quote\Quotation\Model\Quote\Status::STATUS_OPEN,
            \Cart2Quote\Quotation\Model\Quote\Status::STATUS_PROPOSAL_SENT,
        ];

        return in_array($this->getQuote()->getStatus(), $availableStatus);
    }

    /**
     * Format price with custom return
     *
     * @param int $value
     * @param string|int $zero
     * @return string
     */
    public function formatPriceZero($value, $zero)
    {
        if (isset($value) && $value > 0) {
            return $this->formatPrice($value);
        }

        return $zero;
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('quotation_quote_view_search_grid');
    }

    /**
     * Force disable cache
     *
     * @return bool
     */
    protected function getCacheLifetime()
    {
        return false;
    }

    public function getProductImageUrl($product)
    {
        $imageUrl = $this->imageHelper->init(
            $product,
            'product_thumbnail_image'
        )
            ->setImageFile(
                $product->getSmallImage()
            )
            ->resize(
                100,
                100
            )
            ->getUrl();

        return $imageUrl;
    }


    /**
     * Calculate total amount for the (tier) item
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float
     */
    public function getBaseTotalAmount($item): float
    {
        return $this->itemPriceRenderer->getBaseTotalAmount($item);
    }

    /**
     * Calculate total amount for the (tier) item
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float
     */
    public function getTotalAmount($item)
    {
        return $this->itemPriceRenderer->getTotalAmount($item);
    }

    /**
     * Calculate total amount excl. tax for the (tier) item
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float
     */
    public function getTotalAmountExclTax($item)
    {
        $calculateItem = $item;

        return $calculateItem->getRowTotal()
            - $calculateItem->getDiscountAmount()
            + $calculateItem->getDiscountTaxCompensationAmount();
    }

    /**
     * Calculate base total amount excl. tax for the (tier) item
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float
     */
    public function getBaseTotalAmountExclTax($item)
    {
        $calculateItem = $item;

        return $calculateItem->getBaseRowTotal()
            - $calculateItem->getBaseDiscountAmount()
            + $calculateItem->getBaseDiscountTaxCompensationAmount();
    }


    public function getOriginalEditablePrice($item)
    {
       /* if ($item->hasOriginalCustomPrice()) {
            $result = $item->getOriginalCustomPrice() * 1;
        } elseif ($item->hasCustomPrice()) {
            $result = $item->getCustomPrice() * 1;
        } else {
            if ($this->_taxData->priceIncludesTax($this->getStore())) {
                $result = $item->getPriceInclTax() * 1;
            } else {
                $result = $item->getOriginalPrice() * 1;
            }
        }*/

        $result = $item->getPrice() * 1;

        return $result;
    }
}
