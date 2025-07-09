<?php


namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\View;

use OneMoveTwo\Offers\Controller\Adminhtml\Offer\View;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use OneMoveTwo\Offers\Model\OfferRepository;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Backend\Model\Session\Quote;

class ShowUpdateResult extends View
{

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        LoggerInterface $logger,
        Quote $backendQuoteSession,
        OfferRepository $offerRepository,
        private readonly RawFactory $resultRawFactory,
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $resultPageFactory,
            $logger,
            $backendQuoteSession,
            $offerRepository
        );
    }

    /**
     * Show item update result from loadBlockAction
     * - to prevent popup alert with resend data question
     *
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $session = $this->_session;
        if ($session->hasUpdateResult() && is_scalar($session->getUpdateResult())) {
            $resultRaw->setContents($session->getUpdateResult());
        }
        $session->unsUpdateResult();

        return $resultRaw;
    }
}
