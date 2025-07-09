<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View;

use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
use OneMoveTwo\Offers\Model\Data\Offer;

class Form extends \Magento\Backend\Block\Template
{

    protected $_template = 'offer/view/form.phtml';

    public function __construct(
        private readonly Registry $registry,
        private readonly \Magento\Backend\Model\Session\Quote $sessionQuote,
        private readonly \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        private readonly \Magento\Customer\Model\Metadata\FormFactory $customerFormFactory,
        private readonly \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        private readonly \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        private readonly \Magento\Customer\Model\Address\Mapper $addressMapper,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOffer(): Offer
    {
        return $this->registry->registry('current_offer');
    }

    /**
     * Retrieve customer identifier
     */
    public function getCustomerId(): int
    {
        return $this->getSession()->getCustomerId();
    }

    /**
     * Retrieve quote session object
     */
    private function getSession(): \Magento\Backend\Model\Session\Quote
    {
        return $this->sessionQuote;
    }

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

        return $this->jsonEncoder->encode($data);
    }

    /**
     * Retrieve url for loading blocks
     */
    public function getLoadBlockUrl(int $offerId): string
    {
        return $this->getUrl('offers/offer_view/loadBlock', ['entity_id' => $offerId]);
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
}
