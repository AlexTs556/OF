<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\Create;

use OneMoveTwo\Offers\Model\ResourceModel\Offer;

class Header extends \Magento\Sales\Block\Adminhtml\Order\Create\Header
{
    public function __construct(
        private readonly Offer $offerResourceModel,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Helper\View $customerViewHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $customerRepository,
            $customerViewHelper,
            $data
        );
    }

    protected function _toHtml()
    {
        $quotationQuote = $this->getQuote();
        if ($id = $quotationQuote->getId()) {
            //TODO:: Use repository
            $this->offerResourceModel->load($quotationQuote, $id);
            if ($incrementId = $quotationQuote->getIncrementId()) {
                return sprintf('%s%s', __('Edit Offer #'), $incrementId);
            }
        }
        $out = $this->_getCreateOrderTitle();

        return $this->escapeHtml($out);
    }

    /**
     * Generate title for new order creation page.
     *
     * @return string
     */
    protected function _getCreateOrderTitle()
    {
        $customerId = $this->getCustomerId();
        $storeId = $this->getStoreId();
        $out = '';

        if ($customerId && $storeId) {
            $out .= __('Create Offer for %1 in %2', $this->_getCustomerName($customerId), $this->getStore()->getName());
        } elseif (!$customerId && $storeId) {
            $out .= __('Create Offer for New Customer in %1', $this->getStore()->getName());
        } elseif ($customerId && !$storeId) {
            $out .= __('Create Offer for %1', $this->_getCustomerName($customerId));
        } elseif (!$customerId && !$storeId) {
            $out .= __('Create Offer for New Customer');
        }

        return $out;
    }
}
