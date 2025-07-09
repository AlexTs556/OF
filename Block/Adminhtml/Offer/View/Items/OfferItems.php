<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Items;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;
use OneMoveTwo\Offers\Model\Data\Offer;
use Magento\Framework\View\Element\AbstractBlock;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;

class OfferItems extends \Magento\Sales\Block\Adminhtml\Order\View\Items
{
    const FOOTER_TYPE = 'footer';

    private $items;

    public function __construct(
        private readonly \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        private readonly Offer $offer,
        private readonly OfferRepositoryInterface $offerRepository,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $data);
    }

    /**
     * Retrieve rendered item footer html content
     */
    public function getItemFooterHtml(): string
    {
        return $this->getItemRenderer(self::FOOTER_TYPE)->setCanEditQty($this->canEditQty())->toHtml();
    }

    /**
     * Check availability to edit quantity of item
     * - Overwritten because we don't want the payment to be validated on a quote.
     */
    public function canEditQty(): bool
    {
        return true;
    }

    public function getItemsCollection()
    {
        if (!$this->items) {
            $this->items = $this->getItemsGridBlock()->getItems();
        }

        return $this->items;
    }

    /**
     * @throws LocalizedException
     */
    public function getItemsGridBlock(): AbstractBlock
    {
        if (!$block = $this->getChildBlock('items_grid')) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Offer Items render error: "items_grid" needs to be a child of the block "items"')
            );
        }

        return $block;
    }

    public function getItems()
    {
        return $this->getOffer()->getItemsCollection();
    }

    /**
     * Check gift messages availability
     *
     * @param Item|null $item
     * @return bool|null|string
     */
    public function isGiftMessagesAvailable($item = null)
    {
        return $this->getItemsGridBlock()->isGiftMessagesAvailable($item);
    }

    /**
     * Get discount amount
     *
     * @return float
     */
    public function getDiscountAmount()
    {
        return $this->getItemsGridBlock()->getDiscountAmount();
    }

    /**
     * Retrieve rendered item html content
     *
     * @param \Magento\Framework\DataObject $item
     * @return string
     */
    public function getEmptyItemHtml(\Magento\Framework\DataObject $item)
    {
        if ($item->getOrderItem()) {
            $type = $item->getOrderItem()->getProductType();
        } else {
            $type = $item->getProductType();
        }

        $item->setFirst(false);

        return $this->getItemRenderer($type)->setItem($item)->setCanEditQty(true)->setEmpty(true)->toHtml();
    }

    /**
     * Render the rows in combination with the tiers
     * First row that is rendered is the selected tier,
     * after the selected tier the other tiers are rendered ordered by the qty
     *
     * @param Item $item
     * @return string
     */
    public function getTierItemsHtml($item)
    {
        $html = '';
        $tierItemCount = 0;

        // Render selected tier first and afterwards the other tiers
        if ($currentTierItem = $item->getCurrentTierItem()) {
            $this->setDefaultValuesToItem($item, $currentTierItem, $tierItemCount);
            $html .= $this->getItemHtml($item);
            $tierItemCount++;
        }

        foreach ($this->getTierItems($item) as $tierItem) {
            $this->setDefaultValuesToItem($item, $tierItem, $tierItemCount);

            if ($item->getIsSelectedTier()) {
                continue;
            }

            $html .= $this->getItemHtml($item);
            $tierItemCount++;
        }

        return $html;
    }

    /**
     * Set the default values on the quote item
     * - The values can be used in the view
     *
     * @param Item $item
     * @param TierItem $tierItem
     * @param Int $tierItemCount
     * @return Item $item
     */
    public function setDefaultValuesToItem($item, $tierItem, $tierItemCount)
    {
        if ($tierItem) {
            $tierItemId = $tierItem->getId();
            $currentTierItemId = 0;
            if ($item->getCurrentTierItem()) {
                $currentTierItemId = $item->getCurrentTierItem()->getId();
            }

            //sync quote
            if ($item->getQuote()) {
                if (!$tierItem->getQuote()) {
                    $tierItem->setQuote($item->getQuote());
                }
                if ($tierItem->getItem() && !$tierItem->getItem()->getQuote()) {
                    $tierItem->getItem()->setQuote($item->getQuote());
                }
            }

            $item->setTierItem($tierItem);
            $item->setIsFirstTierItem($tierItemCount == 0);
            $item->setTierItemCount($tierItemCount);
            $item->setIsSelectedTier($tierItemId == $currentTierItemId);
        }

        return $item;
    }

    /**
     * Get tier items
     *
     * @param TierItem $item
     * @return array
     */
    public function getTierItems($item)
    {
        if ($tierItems = $item->getTierItems()) {
            return $tierItems;
        }

        return [];
    }

    /**
     * Get sections from quote
     *
     * @return \Cart2Quote\Quotation\Api\Data\Quote\SectionInterface[]
     */
    public function getSections()
    {
        return $this->getQuote()->getSections(['label' => __('Not Assigned to Section')]);
    }

    /**
     * Overwrite the beforeToHtml
     */
    protected function _beforeToHtml()
    {
        $this->setOrder($this->getOffer());
    }

    public function getOffer()
    {
        if (!$quote = $this->_coreRegistry->registry('current_offer')) {
            $this->_coreRegistry->register(
                'current_offer',
                $this->offerRepository->getById($this->getRequest()->getParam('entity_id'))
            );
        }

        return $quote;
    }

    /**
     * Get including/excluding tax message
     *
     * @return \Magento\Framework\Phrase
     */
    public function getInclExclTaxMessage()
    {
        if (1)
        {
            return __('* - Enter custom price including tax');
        } else {
            return __('* - Enter custom price excluding tax');
        }
    }

    /**
     * Get store
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->getQuote()->getStore();
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function getColumns()
    {
        $itemsConfig = $this->getItemsGridConfig();
        $columns = array_key_exists('columns', $this->_data) ? $this->_data['columns'] : [];
        if (isset($itemsConfig)) {
            foreach ($itemsConfig as $itemConfig) {
                if (array_key_exists('visibility', $itemConfig)) {
                    if (!$itemConfig['visibility']) {
                        unset($columns[$itemConfig['name']]);
                    }
                }
            }

            return $columns;
        }

        return parent::getColumns();
    }

    /**
     * Get statuses configuration settings
     *
     * @return array
     */
    public function getItemsGridConfig()
    {
        return [];
    }

}
