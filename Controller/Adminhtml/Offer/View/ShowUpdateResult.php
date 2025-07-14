<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\View;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;

class ShowUpdateResult extends Action
{

    public function __construct(
        Context $context,
        private readonly RawFactory $resultRawFactory,
    ) {
        parent::__construct($context,);
    }

    /**
     * Show item update result from loadBlockAction
     * - to prevent popup alert with resend data question
     *
     */
    public function execute(): ResultInterface|ResponseInterface|Raw
    {
        $resultRaw = $this->resultRawFactory->create();
        $session = $this->_session;
        if ($session->hasUpdateResult() && is_scalar($session->getUpdateResult())) {
            $resultRaw->setContents($session->getUpdateResult());
        }
        $session->unsUpdateResult();

        return $resultRaw;
    }
}
