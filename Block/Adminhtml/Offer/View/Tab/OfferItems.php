<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Tab;

use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Model\Rule;
use OneMoveTwo\Offers\Block\Adminhtml\Offer\AbstractOffer;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use OneMoveTwo\Offers\Model\Service\OfferRule;
use OneMoveTwo\Offers\Model\Data\Offer;

/**
 * Quote information tab
 */
class OfferItems extends AbstractOffer implements TabInterface
{
    public function __construct(
        private readonly OfferRule $offerRule,
        \Magento\Backend\Block\Template\Context $context,
        private readonly \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        private readonly \Magento\Backend\Model\Session\Quote $sessionQuote,
        private readonly Offer $offer,
        private readonly \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        private readonly \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        private readonly \Magento\Customer\Model\Metadata\FormFactory $customerFormFactory,
        private readonly \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        private readonly \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        private readonly \Magento\Customer\Model\Address\Mapper $addressMapper,
        array $data = []
    ) {
        parent::__construct($registry, $adminHelper, $context, $data);
    }

    /**
     * Retrieve source model instance
     */
    public function getSource(): Offer
    {
        return $this->getOffer();
    }

    /**
     * Retrieve quote model instance
     */
    public function getOffer(): Offer
    {
        return $this->registry->registry('current_offer');
    }


    public function getQuote(): CartInterface
    {
        return $this->getOffer()->getMagentoQuote();
    }

    /*public function getQuote()
    {
        return $this->coreRegistry->registry('current_quote');
    }*/

    /**
     * Retrieve quote totals block settings
     */
    public function getQuoteTotalData(): array
    {
        return [
            'can_display_total_due' => true,
            'can_display_total_paid' => true,
            'can_display_total_refunded' => true,
        ];
    }

    /**
     * Get quote info data
     */
    public function getQuoteInfoData(): array
    {
        return ['no_use_quote_link' => true];
    }

    /**
     * Get tracking html
     */
    public function getTrackingHtml(): string
    {
        return $this->getChildHtml('offer_tracking');
    }

    /**
     * Get items html
     */
    public function getItemsHtml(): string
    {
        return $this->getChildHtml('offer_items');
    }

    /**
     * Get payment html
     */
    public function getPaymentHtml(): string
    {
        return $this->getChildHtml('offer_payment');
    }

    /**
     * View URL getter
     */
    public function getViewUrl(int $offerId): string
    {
        return $this->getUrl('offers/*/*', ['entity_id' => $offerId]);
    }

    /**
     * Get Tab label
     */
    public function getTabLabel(): \Magento\Framework\Phrase|string
    {
        return __('Offer Items');
    }
    /**
     * Get Tab title
     */
    public function getTabTitle(): \Magento\Framework\Phrase|string
    {
        return __('Offer Items');
    }

    /**
     * Check if tab can be shown
     */
    public function canShowTab(): true
    {
        return true;
    }

    /**
     * Check if tab is hidden
     */
    public function isHidden(): false
    {
        return false;
    }

    /**
     * Retrieve url for loading blocks
     */
    public function getLoadBlockUrl(int $offerId): string
    {
        return $this->getUrl('offers/offer_view/loadBlock', ['entity_id' => $offerId]);
    }

    /**
     * Retrieve url for save
     */
    public function getSaveUrl(): string
    {
        return $this->getUrl('offers/offer/save');
    }

    /**
     * Retrieve url for form sending
     *
     * @return string
     */
    public function getSendUrl(): string
    {
        return $this->getUrl('offers/offer/send');
    }

    /**
     * Get offer data jason
     */
    public function getOfferDataJson(): string
    {
        $data = [];
        if ($this->getCustomerId()) {
            $data['customer_id'] = $this->getCustomerId();
            $data['addresses'] = [];
            try {
                $addresses = $this->customerRepository->getById($this->getCustomerId())->getAddresses();

                foreach ($addresses as $address) {
                    $addressForm = $this->customerFormFactory->create(
                        'customer_address',
                        'adminhtml_customer_address',
                        $this->addressMapper->toFlatArray($address)
                    );
                    $data['addresses'][$address->getId()] = $addressForm->outputData(
                        \Magento\Eav\Model\AttributeDataFactory::OUTPUT_FORMAT_JSON
                    );
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->_logger->error('Error while getting quote data: ' . $e->getMessage());

            }
        }
        if ($this->getStoreId() !== null) {
            $data['store_id'] = $this->getStoreId();
            $currency = $this->localeCurrency->getCurrency($this->getStore()->getCurrentCurrencyCode());
            $symbol = $currency->getSymbol() ? $currency->getSymbol() : $currency->getShortName();
            $data['currency_symbol'] = $symbol;
            $data['shipping_method_reseted'] = !(bool)$this->getQuote()->getShippingAddress()->getShippingMethod();
            $data['payment_method'] = $this->getQuote()->getPayment()->getMethod();
        }

        //$offerRules = $this->offerRule->getRulesForQuote($this->getOffer());
        //$coupon = $offerRules->getFirstItem();
        $data['coupon'] = ['coupon_code' => null, 'coupon_amount' => null, 'coupon_is_percentage' => false];

        /*if (0) {
            $data['coupon'] = ['coupon_code' => $coupon->getCode()];
            if ($coupon->getSimpleAction() === Rule::BY_PERCENT_ACTION) {
                $data['coupon']['coupon_is_percentage'] = true;
                $data['coupon']['coupon_amount'] = 100 - $coupon->getDiscountAmount();
            } else {
                $data['coupon']['coupon_is_percentage'] = false;
                $data['coupon']['coupon_amount'] = $this->localeCurrency->getCurrency(
                    $this->getStore()->getCurrentCurrencyCode()
                )->toCurrency(
                    $coupon->getDiscountAmount(),
                    ['display' => false]
                );
            }
        }*/

        return $this->jsonEncoder->encode($data);
    }

    /**
     * Retrieve customer identifier
     */
    public function getCustomerId(): int
    {
        return $this->_getSession()->getCustomerId();
    }

    /**
     * Retrieve quote session object
     */
    protected function _getSession(): \Magento\Backend\Model\Session\Quote
    {
        return $this->sessionQuote;
    }

    /**
     * Retrieve store identifier
     */
    public function getStoreId(): int
    {
        return $this->_getSession()->getStoreId();
    }

    /**
     * Retrieve store model object
     */
    public function getStore(): \Magento\Store\Model\Store
    {
        return $this->_getSession()->getStore();
    }

    /**
     * Retrieve create quote model object
     */
    public function getCreateOfferModel(): Offer
    {
        return $this->offer;
    }

    /**
     * Retrieve formated price
     */
    public function formatPrice(float $value): string
    {
        return $this->priceCurrency->format(
            $value,
            true,
            \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->getStore()
        );
    }

    /**
     * Convert price
     */
    public function convertPrice(float $value, bool $format = true): float
    {
        return 1.0055;
    }

    /**
     * Constructor
     */
    protected function _construct(): void
    {
        parent::_construct();
        $this->setId('offers_offer_view_form');
    }
}
