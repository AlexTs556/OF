<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer;
use OneMoveTwo\Offers\Controller\Adminhtml\Offer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Api\Data\OfferInterface;
use Psr\Log\LoggerInterface;

use Magento\Backend\Model\Session\Quote;

class Edit extends Offer
{

    public function __construct(
        Context $context,
        Quote $backendQuoteSession,
        protected readonly Registry $coreRegistry,
        protected readonly PageFactory $resultPageFactory,
        protected readonly LoggerInterface $logger,
        protected readonly OfferRepositoryInterface $offerRepository

    ) {
        parent::__construct($context, $coreRegistry, $resultPageFactory, $logger, $backendQuoteSession, $offerRepository);
    }



    /**
     * @var \Cart2Quote\Quotation\Model\QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Cart2Quote\Quotation\Model\Quote\Email\Sender\QuoteEditedSender
     */
    protected $quoteEditedSender;

   /* public function __construct(
        \Cart2Quote\Quotation\Model\ResourceModel\Quote\Collection $quoteCollection,
        \Cart2Quote\Quotation\Model\Quote\Email\Sender\QuoteEditedSender $quoteEditedSender,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Store\Model\Store $store,
        \Magento\Framework\Escaper $escaper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Cart2Quote\Quotation\Helper\Data $helperData,
        \Cart2Quote\Quotation\Model\QuoteFactory $quoteFactory,
        \Cart2Quote\Quotation\Model\ResourceModel\Status\Collection $statusCollection,
        \Cart2Quote\Quotation\Model\Admin\Quote\Create $quoteCreate,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Cart2Quote\Quotation\Helper\Cloning $cloningHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Model\Session\Quote $backendQuoteSession,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\GiftMessage\Model\Save $giftMessageSave,
        \Magento\Framework\Json\Helper\Data $jsonDataHelper
    ) {
        parent::__construct(
            $quoteCollection,
            $customerRepositoryInterface,
            $store,
            $escaper,
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $helperData,
            $quoteFactory,
            $statusCollection,
            $quoteCreate,
            $scopeConfig,
            $cloningHelper,
            $logger,
            $backendQuoteSession,
            $productHelper,
            $giftMessageSave,
            $jsonDataHelper
        );

        $this->quoteEditedSender = $quoteEditedSender;
    }*/

    /**
     * Cancel original quotation and create new quotation
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            //Cancel Original Quote
            $originalQuote = $this->quoteFactory->create()->load($this->getRequest()->getPost('quote_id'));
            $originalQuote->setData('state', \Cart2Quote\Quotation\Model\Quote\Status::STATE_CANCELED);
            $originalQuote->setData('status', \Cart2Quote\Quotation\Model\Quote\Status::STATUS_CANCELED);
            $originalQuote->save();
            if ($this->quoteEditedSender->send($originalQuote)) {
                $this->messageManager->addSuccessMessage(__('The customer is notified'));
            }

            //Create New Quote
            $newQuote = $this->cloningHelper->cloneQuote($originalQuote);
            $newQuote->setData('state', \Cart2Quote\Quotation\Model\Quote\Status::STATE_OPEN);
            $newQuote->setData('status', \Cart2Quote\Quotation\Model\Quote\Status::STATUS_OPEN);
            $newIncrementId = $this->getNewIncrementId(
                $this->getRequest()->getPost('increment_id'),
                $originalQuote->getStoreId()
            );
            $newQuote->setData('increment_id', $newIncrementId);
            $newQuote->save();
            $resultRedirect->setPath('quotation/quote/view', ['quote_id' => $newQuote->getId()]);
        } catch (\Magento\Framework\Exception\PaymentException $e) {
            $this->getCurrentQuote()->saveQuote();
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->messageManager->addErrorMessage($message);
            }
            $resultRedirect->setPath('quotation/quote/view', ['quote_id' => $this->getCurrentQuote()->getId()]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->messageManager->addErrorMessage($message);
            }
            $resultRedirect->setPath('quotation/quote/view', ['quote_id' => $this->getCurrentQuote()->getId()]);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Quote saving error: %1', $e->getMessage()));
            $resultRedirect->setPath('quotation/quote/view', ['quote_id' => $this->getCurrentQuote()->getId()]);
        }

        return $resultRedirect;
    }

    /**
     * Get increment id for created new
     *
     * @param string $incrementId
     * @param int $storeId
     * @return string
     */
    protected function getNewIncrementId($incrementId, $storeId = 0)
    {
        $prefix = $this->helperData->getQuotePrefix($storeId);

        //remove the prefix from the increment id
        $prefixPosition = strpos($incrementId, $prefix);
        if ($prefixPosition !== false && $prefixPosition === 0) {
            $incrementId = substr_replace($incrementId, '', $prefixPosition, strlen($prefix));
        }

        //get the original increment id without the edit count
        $splitIncrementId = explode('-', $incrementId);
        if (is_array($splitIncrementId) && (count($splitIncrementId) > 1)) {
            //remove only last element form increment id
            array_pop($splitIncrementId);
            $parentIncrementId = implode('-', $splitIncrementId);
        } else {
            $parentIncrementId = $incrementId;
        }

        //add the prefix again
        $parentIncrementId = $prefix . $parentIncrementId;

        //find all quotes with the same prefix
        $quoteCollection = $this->quoteFactory->create()->getCollection();
        $quoteCollection = $quoteCollection
            ->addFieldToSelect('*')
            ->addFieldToFilter(
                'main_table.increment_id',
                ['like' => '%' . $parentIncrementId . '%']
            );
        $quoteCollectionCount = $quoteCollection->getSize();

        //add the edit counter to the increment id
        if ($quoteCollectionCount) {
            return $parentIncrementId . '-' . $quoteCollectionCount;
        }

        return $parentIncrementId;
    }
}
