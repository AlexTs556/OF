<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer;

use Magento\Framework\Exception\LocalizedException;
use OneMoveTwo\Offers\Model\Data\Offer;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Admin;


class AbstractOffer extends \Magento\Backend\Block\Widget
{
    public function __construct(
        private readonly Registry $registry,
        private readonly Admin $adminHelper,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Display price attribute
     */
    public function displayPriceAttribute(string $code, bool $strong = false, string $separator = '<br/>'): string
    {
        return $this->adminHelper->displayPriceAttribute($this->getPriceDataObject(), $code, $strong, $separator);
    }

    /**
     * Get price data object
     * @throws LocalizedException
     */
    public function getPriceDataObject(): mixed
    {
        $obj = $this->getData('price_data_object');
        if ($obj === null) {
            return $this->getOffer();
        }
        return $obj;
    }

    /**
     * Retrieve available quote
     * @throws LocalizedException
     */
    public function getOffer(): Offer
    {
        if ($this->registry->registry('current_offer')) {
            return $this->registry->registry('current_offer');
        }

        throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t get the quote instance right now.'));
    }

    /**
     * Retrieve quote totals block settings
     */
    public function getQuoteTotalData(): array
    {
        return [];
    }

    /**
     * Retrieve quote info block settings
     */
    public function getQuoteInfoData(): array
    {
        return [];
    }

    /**
     * Retrieve subtotal price include tax html formated content
     *
     * @param \Magento\Framework\DataObject $quote
     * @return string
     */
    public function displayShippingPriceInclTax($quote): string
    {
        return '5000000000';

       /* $shipping = $quote->getShippingInclTax();
        if ($shipping) {
            $baseShipping = $quote->getBaseShippingInclTax();
        } else {
            $shipping = $quote->getShippingAmount() + $quote->getShippingTaxAmount();
            $baseShipping = $quote->getBaseShippingAmount() + $quote->getBaseShippingTaxAmount();
        }

        return $this->displayPrices($baseShipping, $shipping, false, ' ');*/
    }

    /**
     * Display prices
     */
    public function displayPrices(float $basePrice, float $price, bool $strong = false, string $separator = '<br/>'): string
    {
        return $this->adminHelper->displayPrices(
            $this->getPriceDataObject(),
            $basePrice,
            $price,
            $strong,
            $separator
        );
    }
}
