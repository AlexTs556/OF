<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\Create;

use OneMoveTwo\Offers\Controller\Adminhtml\Offer as AbstractOfferController;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use OneMoveTwo\Offers\Api\Data\OfferInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Backend\Model\Session\Quote;

class Offer extends AbstractOfferController
{


    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        LoggerInterface $logger,
        Quote $backendQuoteSession,
        OfferRepositoryInterface $offerRepository,
        private readonly OfferInterfaceFactory $offerInterfaceFactory

    ) {
        parent::__construct($context, $coreRegistry, $resultPageFactory, $logger, $backendQuoteSession, $offerRepository);
    }


    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $quote = $this->_getSession()->getQuote();
            $offerId = $this->_getSession()->getOfferId();
            $offer = $this->offerInterfaceFactory->create()->load((int)$offerId);

            var_dump($quote);

            die('Offer create');


            if ($this->_authorization->isAllowed('Cart2Quote_Quotation::actions_view')) {
                $resultRedirect->setPath('quotation/quote/view', ['quote_id' => $quoteId]);
            } else {
                $resultRedirect->setRefererUrl();
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(sprintf(
                '%s: %s',
                __('Cannot convert Quote'),
                $e->getMessage()
            ));

            $resultRedirect->setRefererUrl();
        }

        return $resultRedirect;
    }

    /**
     * ACL check
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('OneMoveTwo_Offers::actions');
    }
}
