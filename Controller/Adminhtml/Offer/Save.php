<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Helper\Product;
use Magento\Framework\Escaper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Quote\Model\CustomerManagement;
use OneMoveTwo\Offers\Model\Converter\QuoteToOffer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\Model\View\Result\Redirect;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Api\OfferManagementInterface;

class Save extends \Magento\Sales\Controller\Adminhtml\Order\Create\Save
{
    public function __construct(
        Context $context,
        Product $productHelper,
        Escaper $escaper,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        private readonly QuoteToOffer $quoteToOfferConverter,
        private readonly CustomerManagement $customerManagement,
        private readonly OfferManagementInterface $offerManagement,
        private readonly OfferRepositoryInterface $offerRepository,
    ) {
        parent::__construct(
            $context,
            $productHelper,
            $escaper,
            $resultPageFactory,
            $resultForwardFactory
        );
    }

    /**
     * Based on: \Magento\Sales\Controller\Adminhtml\Order\Create\Save::execute
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $this->_getOrderCreateModel()->getQuote()->setCustomerId($this->_getSession()->getCustomerId());
            $this->_processActionData('save');

            $this->validate();
            //prepare the quote
            $quoteCreateModel = $this->_getOrderCreateModel()
                ->setIsValidate(true)
                ->importPostData($this->getRequest()->getPost('order'));

            //first unset customer is guest before preparing the customer
            //at this point the customer is created in the backend, so it can't be a guest
            $quoteCreateModel->getQuote()->setCustomerIsGuest('0');

            //prepare the customer data
            $quoteCreateModel->_prepareCustomer();

            $quote = $quoteCreateModel->getQuote();
            $customer = $quote->getCustomer();
            if ($customer) {
                if ($customer->getId() == null) {
                    //A new customer gets created
                    //Customer registration email is also sent by this function
                    $this->customerManagement->populateCustomerInfo($quote);
                    $quoteCreateModel->getQuote()->updateCustomerData($quoteCreateModel->getQuote()->getCustomer());
                }
            }

            $quoteCreateModel->setQuote($quote);
            $quoteCreateModel = $quoteCreateModel->saveQuote();
            $quoteId = $quoteCreateModel->getQuote()->getId();
            $offer = $this->offerRepository->getByQuoteId($quoteId);

            if (!$offer->getId()) {
                $offer = $this->quoteToOfferConverter->convert($quote);
                $this->offerManagement->createOffer($offer);
                $this->_getSession()->clearStorage();
                $this->messageManager->addSuccessMessage(__('You created the offer.'));
                $this->_eventManager->dispatch('admin_offers_offer_create_after', ['offer' => $offer]);
            } else {
                $this->_getSession()->clearStorage();
                $this->offerManagement->updateOffer($offer);
                $this->messageManager->addSuccessMessage(__('You updated the offer.'));
            }

            $this->_getSession()->clearStorage();

            if ($this->_authorization->isAllowed('OneMoveTwo_Offers::actions_view')) {
                $resultRedirect->setPath('offers/offer/view', ['entity_id' => $offer->getId()]);
            } else {
                $resultRedirect->setPath('offers/offer/index');
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->messageManager->addErrorMessage($message);
            }
            $resultRedirect->setPath('offers/offer_create');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Offer saving error: %1', $e->getMessage()));
            $resultRedirect->setPath('offers/offer_create');
        }

        return $resultRedirect;
    }

    /**
     * Validate
     *
     * @throws LocalizedException
     */
    private function validate(): void
    {
        $customerId = $this->_getOrderCreateModel()->getSession()->getCustomerId();
        if ($customerId === null) {
            throw new LocalizedException(__('Please select a customer'));
        }

        if (!$this->_getOrderCreateModel()->getSession()->getStore()->getId()) {
            throw new LocalizedException(__('Please select a store'));
        }

        if (!empty($this->_errors)) {
            foreach ($this->_errors as $error) {
                $this->messageManager->addErrorMessage($error);
            }
            throw new LocalizedException(__('Validation is failed.'));
        }
    }
}
