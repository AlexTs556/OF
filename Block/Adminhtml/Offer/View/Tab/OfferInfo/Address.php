<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Tab\OfferInfo;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use Magento\Backend\Model\Session\Quote;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Address\ToOrderAddress;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

class Address extends Template
{
    public function __construct(
        private readonly Quote $quoteSession,
        private readonly Registry $registry,
        private readonly ToOrderAddress $quoteAddressToOrderAddress,
        private readonly Renderer $addressRenderer,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @throws LocalizedException
     */
    public function getQuote()
    {
        if ($this->registry->registry('current_quote')) {
            return $this->registry->registry('current_quote');
        }

        throw new LocalizedException(__('We can\'t get the quote instance right now.'));
    }

    /**
     * Returns string with formatted address
     */
    public function getFormattedAddress(QuoteAddress $address): ?string
    {
        $salesAddress = $this->quoteAddressToOrderAddress->convert($address, []);
        return $this->addressRenderer->format($salesAddress, 'html');
    }

    /**
     * Get URL to set the default shipping address
     */
    public function getUrlToSetDefaultShippingAddressHtml(): string
    {
        $defaultShippingAddressId = $this->getQuote()->getCustomer()->getDefaultShipping();
        $shippingAddressId = $this->getQuote()->getShippingAddress()->getCustomerAddressId();
        if (($defaultShippingAddressId != null) && $defaultShippingAddressId != $shippingAddressId) {
            $url = $this->getUrl('offers/offer/changeAddress', [
                'quote_id' => $this->getQuote()->getId(),
                'address_type' => 'shipping'
            ]);

            return '<a href="' . $url . '">' . __('Change to default shipping address') . '</a>';
        }

        return '';
    }

    /**
     * Get URL to set the default billing address
     */
    public function getUrlToSetDefaultBillingAddressHtml(): string
    {
        $defaultBillingAddressId = $this->getQuote()->getCustomer()->getDefaultBilling();
        $billingAddressId = $this->getQuote()->getBillingAddress()->getCustomerAddressId();
        if (($defaultBillingAddressId != null) && $defaultBillingAddressId != $billingAddressId) {
            $url = $this->getUrl('quotation/quote/changeAddress', [
                'quote_id' => $this->getQuote()->getId(),
                'address_type' => 'billing'
            ]);

            return '<a href="' . $url . '">' . __('Change to default billing address') . '</a>';
        }

        return '';
    }
}
