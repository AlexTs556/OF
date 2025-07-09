<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Api\Data\OfferInterface;
use Psr\Log\LoggerInterface;
use Magento\Backend\Model\Session\Quote;


//Todo:: Убрать этот класс и убрать его во вресех местах где он наследуеться. Вынести в сервис
abstract class Offer extends Action
{
    private $currentOffer;

    public function __construct(
        Context $context,
        protected readonly Registry $coreRegistry,
        protected readonly PageFactory $resultPageFactory,
        protected readonly LoggerInterface $logger,
        protected readonly Quote $backendQuoteSession,
        protected readonly OfferRepositoryInterface $offerRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Init layout, menu and breadcrumb
     *
     * @return Page
     */
    protected function _initAction(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('OneMoveTwo_Offers::offers');
        $resultPage->addBreadcrumb(__('OneMoveTwo'), __('OneMoveTwo'));
        $resultPage->addBreadcrumb(__('Offers'), __('Offers'));

        return $resultPage;
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('OneMoveTwo_Offers::offers');
    }

    protected function _initOffer(): OfferInterface|false
    {
        $id = $this->getRequest()->getParam('entity_id');

        try {
            $offer = $this->offerRepository->getById((int)$id);
        } catch (NoSuchEntityException | InputException $e) {
            $this->messageManager->addErrorMessage(__('This offer no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            return false;
        }

        $this->coreRegistry->register('current_offer', $offer);
        return $offer;
    }

    protected function _initSession(): static
    {
        /**
         * Identify quote
         */
        if ($offerId = $this->getRequest()->getParam('entity_id')) {
            $this->_getSession()->setOfferId((int)$offerId);
        } elseif ($offer = $this->getCurrentOffer()) {
            $this->_getSession()->setQuoteId((int)$offer->getQuoteId());
        }

        /**
         * Identify customer
         */
        $this->_getSession()->setCustomerId(null);
        if ($customerId = $this->getRequest()->getParam('customer_id')) {
            $this->_getSession()->setCustomerId((int)$customerId);
        } elseif ($offer = $this->getCurrentOffer()) {
            if ($customerId = $offer->getCustomerId()) {
                $this->_getSession()->setCustomerId((int)$customerId);
            }
        }

        /**
         * Identify store
         */
       if ($storeId = $this->getRequest()->getParam('store_id')) {
            $this->_getSession()->setStoreId((int)$storeId);
        } elseif ($offer = $this->getCurrentOffer()) {
            if ($storeId = $offer->getStoreId()) {
                $this->_getSession()->setStoreId((int)$storeId);
            }
        }


        return $this;
    }


    protected function getCurrentOffer()
    {
        if ($this->currentOffer === null) {
            $offer = $this->coreRegistry->registry('current_offer');
            if ($offer) {
                $this->currentOffer = $offer;
            }
        }

        return $this->currentOffer;
    }

    /**
     * @throws LocalizedException
     */
    private function getMagentoQuote(): CartInterface
    {
        if (!$this->coreRegistry->registry('current_quote')) {
            $currentOffer = $this->getCurrentOffer();
            $quote = $currentOffer->getMagentoQuote();
            $this->coreRegistry->unregister('current_quote');
            $this->coreRegistry->register('current_quote', $quote);
        }

        return $this->coreRegistry->registry('current_quote');
    }


    protected function _initMagentoQuote()
    {
        if ($this->coreRegistry->registry('current_quote')) {
            return $this->coreRegistry->registry('current_quote');
        }

        $currentOffer = $this->getCurrentOffer();
        $quote = $currentOffer->getMagentoQuote();
        $this->coreRegistry->unregister('current_quote');
        $this->coreRegistry->register('current_quote', $quote);

        return $this->coreRegistry->registry('current_quote');
    }


    protected function _processData()
    {
        return $this->_processActionData();
    }

    protected function _getSession()
    {
        return $this->backendQuoteSession;
    }

    protected function _processActionData($action = null)
    {
        $eventData = [
            'offer_model' => $this->getCurrentOffer(),
            'request_model' => $this->getRequest(),
            'session' => $this->_getSession(),
        ];

        $this->_eventManager->dispatch('adminhtml_quotation_quote_view_process_data_before', $eventData);
        //$data = $this->getRequest()->getPost('offer');

        /**
         * Saving order data
         */
       /* if ($data) {
            $this->getCurrentQuote()->importPostData($data);
            $quote = $this->getRequest()->getParam('quote', false);
            if (!isset($data['expiry_enabled'])) {
                $this->getCurrentQuote()->setExpiryEnabled(false);
            }
            if (!isset($data['reminder_enabled'])) {
                $this->getCurrentQuote()->setReminderEnabled(false);
            }
            if (isset($quote['status'])) {
                $newStatus = $quote['status'];
                $status = $this->_statusCollection->getItemByColumnValue('status', $newStatus);
                $state = $status->getState();
                $this->getCurrentQuote()->setState($state);
            }
        }*/

        /**
         * Set ignore stock check!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
         */
       /* if (!$this->helperData->isStockEnabledBackend()) {
            $this->getCurrentQuote()->setIsSuperMode(true);
        }*/

        /**
         * Prevent setting null quantity on stock check
         */
        //$this->getCurrentQuote()->setIgnoreOldQty(true);

        /**
         * Set correct currency
         */
        //$this->processCurrency();

        /**
         * Initialize catalog rule data
         */
        //$this->getCurrentQuote()->initRuleData();

        /**
         * Process addresses
         */
        //$this->_processAddresses();

        /**
         * Process shipping
         */
       // $this->_processShipping();

        /**
         * Adding product to quote from shopping cart, wishlist etc.
         */
       /* if ($productId = (int)$this->getRequest()->getPost('add_product')) {
            $this->get()->addProduct($productId, $this->getRequest()->getPostValue());
        }*/

        /**
         * Adding products to quote from special grid
         */
      /*  if ($this->getRequest()->has('item') && !$this->getRequest()->getPost('update_items') && !($action == 'save')) {
            $items = $this->getRequest()->getPost('item');
            $items = $this->_processFiles($items);
            $this->getCurrentQuote()->addProducts($items);
        }*/

        /**
         * Set Subtotal Proposal
         */
       // $this->_setSubtotalProposal();

        /**
         * Update quote items
         */
       // $this->_updateQuoteItems();

        /**
         * Remove quote item
         */
        //$this->_removeQuoteItem();

        //$this->getCurrentQuote()->updateBaseCustomPrice();

        /**
         * Save payment data
         */
        /*if ($paymentData = $this->getRequest()->getPost('payment')) {
            $this->getCurrentQuote()->getPayment()->addData($paymentData);
        }*/


        /*$couponCode = '';
        if (isset($data['coupon']['code'])) {
            $couponCode = trim($data['coupon']['code']);
        }

        if (!empty($couponCode)) {
            $isApplyDiscount = false;
            foreach ($this->getCurrentQuote()->getAllItems() as $item) {
                if (!$item->getNoDiscount()) {
                    $isApplyDiscount = true;
                    break;
                }
            }
            if (!$isApplyDiscount) {
                $this->messageManager->addErrorMessage(
                    __(
                        '"%1" coupon code was not applied. Do not apply discount is selected for item(s)',
                        $this->escaper->escapeHtml($couponCode)
                    )
                );
            } elseif ($this->getCurrentQuote()->getCouponCode() !== $couponCode) {
                $this->messageManager->addErrorMessage(
                    __(
                        '"%1" coupon code is not valid.',
                        $this->escaper->escapeHtml($couponCode)
                    )
                );
            } else {
                $this->messageManager->addSuccessMessage(__('The coupon code has been accepted.'));
            }
        }

        $eventData = [
            'quote_model' => $this->getCurrentQuote(),
            'request' => $this->getRequest()->getPostValue(),
        ];
        $this->_eventManager->dispatch('adminhtml_quotation_quote_view_process_data', $eventData);

        $this->getCurrentQuote()->saveQuote();*/

        $eventData = [
            'quote_model' => $this->getCurrentQuote(),
            'request' => $this->getRequest()->getPostValue(),
        ];
        $this->_eventManager->dispatch('adminhtml_quotation_quote_view_process_data', $eventData);

        $this->getCurrentQuote()->saveQuote();

        return $this;
    }


    protected function _reloadOffer()
    {
       /* $this->currentOffer = $this->quoteFactory->create()->load($this->getCurrentQuote()->getId());
        $this->_coreRegistry->unregister('current_quote');
        $this->_coreRegistry->register('current_quote', $this->_currentQuote);*/

        return $this;
    }
}
