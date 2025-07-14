<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\Redirect;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Api\Data\OfferInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote as QuoteModel;

/**
 * View offer controller for admin area
 *
 * Handles displaying offer details in the admin panel
 */
class View extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const string ADMIN_RESOURCE = 'OneMoveTwo_Offers::actions_view';

    /**
     * Registry key for current offer
     */
    private const string CURRENT_OFFER_REGISTRY_KEY = 'current_offer';

    /**
     * Registry key for current quote
     */
    private const string CURRENT_QUOTE_REGISTRY_KEY = 'current_quote';

    /**
     * Current offer instance
     */
    private ?OfferInterface $currentOffer = null;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param Quote $backendQuoteSession
     * @param OfferRepositoryInterface $offerRepository
     */
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
     * Execute view action
     *
     * @return Page|Redirect
     */
    public function execute(): Page|Redirect
    {
        try {
            $this->initOffer();
            $this->initQuoteSession();
            $this->initMagentoQuote();
            $resultPage = $this->initAction();
            $this->setPageTitle($resultPage, $this->getCurrentOffer());
            return $resultPage;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(__('Exception occurred during offer load'));

            return $this->redirectToSalesOrderIndex();
        }
    }

    /**
     * Get backend quote session
     *
     * @return Quote
     */
    protected function getSession(): Quote
    {
        return $this->backendQuoteSession;
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

    /**
     * Get current offer from registry
     *
     * @return OfferInterface|null
     */
    private function getCurrentOffer(): ?OfferInterface
    {
        if ($this->currentOffer === null) {
            $offer = $this->coreRegistry->registry(self::CURRENT_OFFER_REGISTRY_KEY);

            if ($offer instanceof OfferInterface) {
                $this->currentOffer = $offer;
            }
        }

        return $this->currentOffer;
    }

    /**
     * Initialize page with menu and breadcrumbs
     *
     * @return Page
     */
    private function initAction(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('OneMoveTwo_Offers::offers');
        $resultPage->addBreadcrumb(__('OneMoveTwo'), __('OneMoveTwo'));
        $resultPage->addBreadcrumb(__('Offers'), __('Offers'));

        return $resultPage;
    }

    /**
     * Set page title based on offer data
     *
     * @param Page $resultPage
     * @param OfferInterface $offer
     * @return void
     */
    private function setPageTitle(Page $resultPage, OfferInterface $offer): void
    {
        $pageConfig = $resultPage->getConfig();
        $pageConfig->getTitle()->prepend(__('Offer'));

        $offerNumber = $offer->getOfferNumber();
        if ($offerNumber) {
            $pageConfig->getTitle()->prepend(sprintf("#%s", $offerNumber));
        }
    }

    /**
     * Redirect to offers list page
     *
     * @return Redirect
     */
    private function redirectToOffersList(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('offers/*/');

        return $resultRedirect;
    }

    /**
     * Redirect to sales order index page
     *
     * @return Redirect
     */
    private function redirectToSalesOrderIndex(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/index');

        return $resultRedirect;
    }
}
