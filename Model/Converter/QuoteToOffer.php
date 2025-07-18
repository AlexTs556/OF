<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Converter;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use mysql_xdevapi\Exception;
use OneMoveTwo\Offers\Api\Data\OfferInterface;
use OneMoveTwo\Offers\Api\Data\OfferInterfaceFactory;
use OneMoveTwo\Offers\Model\Config\ConfigProvider;
use OneMoveTwo\Offers\Service\OfferNumberGeneratorService;

readonly class QuoteToOffer
{
    public function __construct(
        private OfferInterfaceFactory $offerFactory,
        private ConfigProvider $configProvider,
        private OfferNumberGeneratorService $offerNumberGeneratorService
    ) {
    }

    /**
     * Convert Magento quote to offer
     *
     * @param CartInterface $quote
     * @return OfferInterface
     * @throws LocalizedException
     */
    public function convert(CartInterface $quote): OfferInterface
    {
        try {
            $offer = $this->offerFactory->create();

            // Map fields from quote to offer
            $offer->setQuoteId((int) $quote->getId())
                ->setOfferNumber($this->offerNumberGeneratorService->generateOfferNumber(1, (int)$quote->getStoreId()))
                ->setOfferName('')
                ->setCustomerId($quote->getCustomerId())
                ->setCustomerIsGuest((bool)$quote->getCustomerIsGuest() ?? false)
                ->setCustomerEmail($quote->getCustomer() ? $quote->getCustomer()->getEmail() : null)
                ->setCustomerName($this->getCustomerName($quote))
                ->setStatus($this->getDefaultStatus())
                ->setVersion(1) // Initial version
                ->setParentOfferId(null) // No parent for new offers
                ->setStoreId((int)$quote->getStoreId())
                ->setSubtotal($quote->getSubtotal() ?? 0.0)
                ->setDiscountAmount($quote->getSubtotalWithDiscount() ? ($quote->getSubtotal() - $quote->getSubtotalWithDiscount()) : 0.0)
                ->setShippingAmount(0.0) // Will be set later if needed
                ->setTaxAmount(0.0) // Will be set later if needed
                ->setGrandTotal($quote->getGrandTotal() ?? 0.0)
                ->setPrepaymentAmount(0.0) // Empty for technical fields
                ->setPrepaymentPercent(0.0) // Empty for technical fields
                ->setItemsCount(count($quote->getItems() ?? []))
                ->setItemsQty($quote->getItemsQty() ?? 0.0);

            // Set the expiry date if configured
            $expiryDays = (int)$this->configProvider->getDefaultExpiryDays();
            if ($expiryDays > 0) {
                $expiryDate = new \DateTime();
                $expiryDate->modify("+{$expiryDays} days");
                $offer->setExpiryDate($expiryDate->format('Y-m-d H:i:s'));
            }
        } catch (Exception $exception) {
            throw new LocalizedException(__('We can\'t convert the quote to offer. Error %1', $exception->getMessage()));
        }

        return $offer;
    }

    /**
     * Get the customer name from quote
     *
     * @param CartInterface $quote
     * @return string|null
     */
    private function getCustomerName(CartInterface $quote): ?string
    {
        if ($quote->getCustomer()) {
            $customer = $quote->getCustomer();
            return trim(sprintf(
                '%s %s',
                $customer->getFirstname() ?? '',
                $customer->getLastname() ?? ''
            ));
        }

        if ($quote->getBillingAddress()) {
            $address = $quote->getBillingAddress();
            return trim(sprintf(
                '%s %s',
                $address->getFirstname() ?? '',
                $address->getLastname() ?? ''
            ));
        }

        return null;
    }

    /**
     * Get default status for new offers from configuration
     *
     * @return string
     */
    private function getDefaultStatus(): string
    {
        return $this->configProvider->getDefaultStatus() ?: 'draft';
    }
}
