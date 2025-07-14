<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;
use Magento\Framework\View\Result\Page;

class Index extends Action
{
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        protected readonly PageFactory $resultPageFactory,
    ) {
        parent::__construct($context);
    }

    /**
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('OneMoveTwo_Offers::offers');
        $resultPage->addBreadcrumb(__('OneMoveTwo'), __('OneMoveTwo'));
        $resultPage->addBreadcrumb(__('Offers'), __('Offers'));
        $resultPage->getConfig()->getTitle()->prepend(__('Offers'));
        return $resultPage;
    }
}
