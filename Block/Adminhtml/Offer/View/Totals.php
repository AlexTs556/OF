<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Adminhtml quotation quote create totals block
 */
class Totals extends \Magento\Sales\Block\Adminhtml\Order\Create\Totals
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Cart2Quote\Quotation\Api\QuoteRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Cart2Quote\Quotation\Helper\Currency
     */
    protected $currencyHelper;

    /**
     * @var string
     */
    protected $_defaultRenderer = \Cart2Quote\Quotation\Block\Adminhtml\Quote\View\Totals\DefaultTotals::class;

    /**
     * Totals constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Sales\Helper\Data $salesData
     * @param \Magento\Sales\Model\Config $salesConfig
     * @param \Magento\Framework\Registry $registry
     * @param \Cart2Quote\Quotation\Api\QuoteRepositoryInterface $quoteRepository
     * @param \Cart2Quote\Quotation\Helper\Currency $currencyHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Sales\Helper\Data $salesData,
        \Magento\Sales\Model\Config $salesConfig,
        \Magento\Framework\Registry $registry,
        \Cart2Quote\Quotation\Api\QuoteRepositoryInterface $quoteRepository,
        \Cart2Quote\Quotation\Helper\Currency $currencyHelper,
        array $data
    ) {
        $this->registry = $registry;
        $this->quoteRepository = $quoteRepository;
        $this->currencyHelper = $currencyHelper;
        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $salesData, $salesConfig, $data);
    }

    /**
     * Get header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Quote Totals');
    }

    /**
     * Check allow to send new quote confirmation email
     *
     * @return bool
     */
    public function canSendNewQuoteConfirmationEmail()
    {
        return false;
    }

    /**
     * Retrieve formated price
     *
     * @param float $value
     * @return string
     */
    public function formatPrice($value)
    {
        return$this->getQuote()->formatPrice($value);
    }

    /**
     * Retrieve quote model object
     *
     * @return \Cart2Quote\Quotation\Model\Quote
     */
    public function getQuote()
    {
        if (!$quote = $this->registry->registry('current_quote')) {
            $this->registry->register(
                'current_quote',
                $this->quoteRepository->get($this->getRequest()->getParam('quote_id'))
            );
        }

        return $quote;
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('quotation_quote_view_totals');
    }

    /**
     * Display submit quote button
     *
     * @return bool
     */
    public function displaySubmitQuoteButton()
    {
        $quote = $this->getQuote();

        return $quote->getStatus() != \Cart2Quote\Quotation\Model\Quote\Status::STATUS_PHONE_ONLY;
    }
}
