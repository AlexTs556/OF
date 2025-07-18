<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\View;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use OneMoveTwo\Offers\Api\Data\OfferHistoryInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferManagementInterface;
use Psr\Log\LoggerInterface;
use OneMoveTwo\Offers\Model\OfferRepository;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Backend\Model\Session\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Api\Data\CartInterface;
use OneMoveTwo\Offers\Api\Data\OfferInterface;
use OneMoveTwo\Offers\Api\Data\OfferItemInterfaceFactory;
use Magento\Backend\App\Action;
use OneMoveTwo\Offers\Service\OfferAttachmentManagementService;
use OneMoveTwo\Offers\Helper\Data as OfferHelper;
use OneMoveTwo\Offers\Model\Converter\QuoteToOfferItem;

class LoadBlock extends Action
{
    /**
     * Registry key for current offer
     */
    private const string CURRENT_OFFER_REGISTRY_KEY = 'current_offer';

    /**
     * Registry key for current quote
     */
    private const string CURRENT_QUOTE_REGISTRY_KEY = 'current_quote';

    public function __construct(
        Context $context,
        private readonly QuoteRepository $quoteRepository,
        private readonly Registry $coreRegistry,
        private readonly PageFactory $resultPageFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger,
        private readonly ObjectFactory $objectFactory,
        private readonly OfferItemInterfaceFactory $offerItemFactory,
        private readonly OfferHistoryInterfaceFactory $offerHistoryFactory,
        private readonly OfferAttachmentManagementService $attachmentManagement,
        private readonly OfferManagementInterface $offerManagement,
        private readonly QuoteToOfferItem $quoteToOfferItemConverter,
        private readonly Quote $backendQuoteSession,
        private readonly OfferRepository $offerRepository,
        private readonly RawFactory $resultRawFactory,
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
            $this->initQuoteSession();
            $this->initMagentoQuote();
            $this->processActionData();
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

        $asJson = $request->getParam('json');
        $block = $request->getParam('block');
        $resultPage = $this->resultPageFactory->create();

        if ($this->getRequest()->has('item') && $asJson) {
            $resultPage->addHandle('offers_offer_view_load_block_json');
        } elseif ($asJson) {
            $resultPage->addHandle('offers_offer_view_load_block_json');
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
     * Initialize offer from request parameters
     *
     * @throws LocalizedException
     */
    private function initOffer(): void
    {
        $entityId = $this->getRequest()->getParam('entity_id');

        if (!$entityId) {
            $message = __('Offer ID is required.');
            $this->messageManager->addErrorMessage($message);
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            throw new LocalizedException($message);
        }

        try {
            $offer = $this->offerRepository->getById((int)$entityId);
            $this->coreRegistry->register(self::CURRENT_OFFER_REGISTRY_KEY, $offer);
        } catch (NoSuchEntityException $e) {
            $message = __('This offer no longer exists.');
            $this->messageManager->addErrorMessage($message);
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            throw new LocalizedException($message);
        }
    }

    /**
     * Initialize quote session with offer data
     *
     * @return void
     */
    private function initQuoteSession(): void
    {
        $offer = $this->getCurrentOffer();

        if (!$offer) {
            return;
        }

        // Reset session to clean state
        $this->getSession()->_resetState();

        // Set quote ID if available
        if ($quoteId = $offer->getQuoteId()) {
            $this->getSession()->setQuoteId((int)$quoteId);
        }

        // Set customer ID if available
        if ($customerId = $offer->getCustomerId()) {
            $this->getSession()->setCustomerId((int)$customerId);
        }

        // Set store ID if available
        if ($storeId = $offer->getStoreId()) {
            $this->getSession()->setStoreId((int)$storeId);
        }
    }

    /**
     * Initialize Magento quote in registry
     *
     * @return void
     */
    private function initMagentoQuote(): void
    {
        // Check if quote is already registered
        if ($this->coreRegistry->registry(self::CURRENT_QUOTE_REGISTRY_KEY)) {
            return;
        }

        $offer = $this->getCurrentOffer();

        if (!$offer) {
            return;
        }

        // Try to get quote from session first
        $quote = $this->getSession()->getQuote();

        if ($quote->getId()) {
            $this->coreRegistry->register(self::CURRENT_QUOTE_REGISTRY_KEY, $quote);
        }

        // Override with offer's Magento quote if available
        $quote = $offer->getMagentoQuote();

        if ($quote->getId()) {
            $this->coreRegistry->unregister(self::CURRENT_QUOTE_REGISTRY_KEY);
            $this->coreRegistry->register(self::CURRENT_QUOTE_REGISTRY_KEY, $quote);
        }
    }

   /* private function initSession(): void
    {

        if ($offerId = $this->getRequest()->getParam('entity_id')) {
            $this->_getSession()->setOfferId((int)$offerId);
        } elseif ($offer = $this->getCurrentOffer()) {
            $this->_getSession()->setQuoteId((int)$offer->getQuoteId());
        }


        $this->_getSession()->setCustomerId(null);
        if ($customerId = $this->getRequest()->getParam('customer_id')) {
            $this->_getSession()->setCustomerId((int)$customerId);
        } elseif ($offer = $this->getCurrentOffer()) {
            if ($customerId = $offer->getCustomerId()) {
                $this->_getSession()->setCustomerId((int)$customerId);
            }
        }


        if ($storeId = $this->getRequest()->getParam('store_id')) {
            $this->_getSession()->setStoreId((int)$storeId);
        } elseif ($offer = $this->getCurrentOffer()) {
            if ($storeId = $offer->getStoreId()) {
                $this->_getSession()->setStoreId((int)$storeId);
            }
        }

    }*/

    private function getMagentoQuote(): CartInterface
    {
        return $this->coreRegistry->registry('current_quote');
    }

    private function getCurrentOffer(): OfferInterface
    {
        return $this->coreRegistry->registry('current_offer');
    }

    /**
     * Get backend quote session
     *
     * @return Quote
     */
    private function getSession(): Quote
    {
        return $this->backendQuoteSession;
    }

    /**
     * @throws LocalizedException
     */
    private function processActionData(): void
    {
        $eventData = [
            'offer_model' => $this->getCurrentOffer(),
            'request_model' => $this->getRequest(),
            'session' => $this->_getSession(),
        ];

        $this->_eventManager->dispatch('adminhtml_offer_view_process_data_before', $eventData);

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
            $this->addItemsToQuote($items);
        }

        $this->saveQuote();

        $this->updateOfferData();

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
        $this->_eventManager->dispatch('adminhtml_offer_view_process_data_after', $eventData);

       // $this->quoteRepository-sa

        //$this->getMagentoQuote()->saveQuote();
    }

    private function addItemsToQuote(array $items): void
    {
        foreach ($items as $productId => $productData) {
            try {
                $product = $this->productRepository->getById($productId, false, $this->getCurrentOffer()->getStoreId());

                if (isset($productData['qty'])) {
                    $request = $this->objectFactory->create(['qty' => $productData['qty']]);
                }

                $item = $this->getMagentoQuote()->addProduct(
                    $product,
                    $request,
                    AbstractType::PROCESS_MODE_FULL
                );

                $item->save();
            } catch (LocalizedException|Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

    }

    /**
     * @throws LocalizedException
     */
    private function updateOfferData(): void
    {
        try {
            $data = $this->getRequest()->getPostValue();
            $offer = $this->getCurrentOffer();

            // Handle offer number auto-generation
           /* if (!isset($data['offer_number_auto_generate']) && $data['offer_number_auto_generate'] === 'on') {
                // Set a flag to indicate auto-generation is needed
                // The actual generation will happen in the service
                $offer->setData('auto_generate_number', true);
            }*/

            $offer->addData($data);

            $offerItems = [];
            if ($this->getRequest()->has('item') && !$this->getRequest()->getPost('update_items')) {
                $quoteItems = $this->getMagentoQuote()->getAllItems();
                foreach ($quoteItems as $quoteItem) {
                    $offerItems[] = $this->quoteToOfferItemConverter->convert($quoteItem, $offer->getEntityId());
                }
            }

            $attachmentsFiles = [];

            $files = (array)($this->getRequest()->getFiles('attachments') ?? []);
            if (!empty($files)) {
                $attachmentsInfo = !empty($data['attachments_info'])
                    ? json_decode($data['attachments_info'], true) ?: []
                    : [];

                foreach ($attachmentsInfo as $index => $item) {
                    if (!empty($files[$index])) {
                        $file = $files[$index];
                        $file['id'] = $item['id'];
                        $attachmentsFiles['add'][] = $file;
                    }
                }
            }

            if (!empty($data['delete_attachments'])) {
                $attachmentsFiles['remove'] = json_decode(
                    $this->getRequest()->getPostValue('delete_attachments'),
                    true
                );
            }

            $updatedOffer = $this->offerManagement->updateOffer($offer, $offerItems, $attachmentsFiles);


            // Add history comment if provided
            //if (!empty($data['comment'])) {
               // $this->addHistoryComment($updatedOffer, $data['comment']);
            //}

            // Send email if requested
            //if (!empty($data['offer_email'])) {
                //$this->offerManagement->sendOfferEmail($updatedOffer->getEntityId());
            //}

        } catch (\Exception $e) {
            $this->logger->error('Error saving offer: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $data ?? [],
                'offer_id' => $data['offer_id'] ?? null
            ]);

            throw new LocalizedException(__('Error saving offer %1', $data['offer_id']));
        }
    }

    /**
     * Add history comment
     */
    private function addHistoryComment($offer, string $comment): void
    {
        $offerHistory = $this->offerHistoryFactory->create();
        $offerHistory->setOfferId((int)$offer->getEntityId());
        $offerHistory->setStatus($offer->getStatus() ?? 'Updated');
        $offerHistory->setComment($comment);
        $offerHistory->setIsCustomerNotified(false);
        $offerHistory->setVisibleOnStorefront(false);
        $offerHistory->setCreatedByName($this->getAdminUserName());

        $this->historyRepository->save($offerHistory);
    }


    /*private function setOfferData($offer, array $data): void
    {
        if (isset($data['offer_name'])) {
            $offer->setOfferName($data['offer_name']);
        }

        if (isset($data['expiry_date'])) {
            $offer->setExpiryDate($data['expiry_date']);
        }

        // Handle offer number generation
        if (isset($data['offer_number_auto_generate']) && $data['offer_number_auto_generate']) {
            // Auto-generate offer number
            $offer->setOfferNumber($this->generateOfferNumber());
        } elseif (isset($data['offer_number'])) {
            // Use provided offer number
            $offer->setOfferNumber($data['offer_number']);
        }
    }*/



    private function saveQuote(): void
    {
        $magentoQuote = $this->getMagentoQuote();

        if (!$magentoQuote->getId()) {
            return;
        }

        $magentoQuote->collectTotals();
        $this->quoteRepository->save($magentoQuote);

    }
}
