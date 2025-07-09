<?php

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\View;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use OneMoveTwo\Offers\Controller\Adminhtml\Offer\View;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use OneMoveTwo\Offers\Model\OfferRepository;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Backend\Model\Session\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Api\Data\CartInterface;
use OneMoveTwo\Offers\Api\Data\OfferInterface;
use Magento\Backend\App\Action;

class LoadBlock extends Action
{

    public function __construct(
        Context $context,
        private readonly QuoteRepository $quoteRepository,
        private readonly Registry $coreRegistry,
        private readonly PageFactory $resultPageFactory,
        private readonly LoggerInterface $logger,
        protected readonly Quote $backendQuoteSession,
        private readonly OfferRepository $offerRepository,
        protected readonly RawFactory $resultRawFactory,
    ) {
        parent::__construct($context);
    }

    /**
     * Load the view block
     */
    public function execute()
    {
        $request = $this->getRequest();
        try {
            $this->initOffer();
            $this->initSession();
            $this->processActionData();
            $magentoQuote = $this->getMagentoQuote();
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }
       // $this->_reloadQuote();

        $asJson = $request->getParam('json');
        $block = $request->getParam('block');

        $resultPage = $this->resultPageFactory->create();
        //$maxQtyForReload = $this->helperData->getMaxProductQtyForReload();
        $maxQtyForReload = 10;

        if ($this->getRequest()->has('item') && $asJson) {
            if (count($this->getRequest()->getPost('item')) > $maxQtyForReload ||
                count($magentoQuote->getAllVisibleItems()) > $maxQtyForReload) {
                $resultPage->addHandle('offers_offer_view_load_block_reload');
            } else {
                $resultPage->addHandle('offers_offer_view_load_block_json');
            }
        } elseif ($asJson) {
            $resultPage->addHandle('offers_offer_view_load_block_json');
        } else {
            $resultPage->addHandle('offers_offer_view_load_block_plain');
        }

        if ($block) {
            $blocks = explode(',', $block);
            if ($asJson && !in_array('message', $blocks)) {
                $blocks[] = 'message';
            }

            foreach ($blocks as $block) {
                $resultPage->addHandle('offers_offer_view_load_block_' . $block);
            }
        }

        $result = $resultPage->getLayout()->renderElement('content');
        if ($request->getParam('as_js_varname')) {
            $this->_session->setUpdateResult($result);

            return $this->resultRedirectFactory->create()->setPath('offers/*/showUpdateResult');
        }

        return $this->resultRawFactory->create()->setContents($result);
    }

    /**
     * @throws LocalizedException
     */
    private function initOffer(): void
    {
        $id = $this->getRequest()->getParam('entity_id');

        try {
            $offer = $this->offerRepository->getById((int)$id);
        } catch (NoSuchEntityException $e) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            throw new LocalizedException(__('This offer no longer exists.'));
        }

        $this->coreRegistry->register('current_offer', $offer);

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

    private function getCurrentOffer(): OfferInterface
    {
        return $this->coreRegistry->registry('current_offer');
    }

    private function initSession(): void
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

    }

    private function processActionData(): void
    {
        $eventData = [
            'offer_model' => $this->getCurrentOffer(),
            'request_model' => $this->getRequest(),
            'session' => $this->_getSession(),
        ];

        $this->_eventManager->dispatch('adminhtml_quotation_quote_view_process_data_before', $eventData);

        /**
         * Adding product to quote from shopping cart, wishlist etc.
         */
        /*if ($productId = (int)$this->getRequest()->getPost('add_product')) {
            $this->getMagentoQuote()->addProduct($productId, $this->getRequest()->getPostValue());
        }*/

        /**
         * Adding products to quote from special grid
         */
        if ($this->getRequest()->has('item') && !$this->getRequest()->getPost('update_items')) {
            $items = $this->getRequest()->getPost('item');
            $this->getCurrentOffer()->addItems($items);
        }

        /**
         * Set Subtotal Proposal
         */
        //$this->_setSubtotalProposal();

        /**
         * Update quote items
         */
        //$this->_updateQuoteItems();

        $eventData = [
            'offer_model' => $this->getCurrentOffer(),
            'request' => $this->getRequest()->getPostValue(),
        ];
        $this->_eventManager->dispatch('adminhtml_offers_offer_view_process_data', $eventData);

        $this->saveQuote();

       // $this->quoteRepository-sa

        //$this->getMagentoQuote()->saveQuote();

    }


    public function saveQuote()
    {
        $magentoQuote = $this->getMagentoQuote();

        if (!$magentoQuote->getId()) {
            return $this;
        }

       // $magentoQuote->t();
        $magentoQuote->collectTotals();
        $this->quoteRepository->save($magentoQuote);
        //$magentoQuote->save();

        return $this;
    }
}
