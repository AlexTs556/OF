<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Session\Quote;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Framework\Locale\CurrencyInterface;

class Data extends AbstractCreate
{

    public function __construct(
        private readonly CurrencyInterface $localeCurrency,
        Context $context,
        Quote $sessionQuote,
        Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $data);
    }

    /**
     * Retrieve currency name by code
     */
    public function getCurrencySymbol(string $code): string
    {
        $currency = $this->localeCurrency->getCurrency($code);
        return $currency->getSymbol() ? $currency->getSymbol() : $currency->getShortName();
    }

    /**
     * Retrieve current quote currency code
     */
    public function getCurrentCurrencyCode(): string
    {
        return $this->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get info data
     */
    public function getInfoData(): array
    {
        return ['no_use_quote_link' => true];
    }
}
