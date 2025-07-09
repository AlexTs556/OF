<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\Create;

use Magento\Backend\Model\View\Result\Page;

class Index extends \Magento\Sales\Controller\Adminhtml\Order\Create\Index
{
    /**
     * Index page
     *
     * @return Page
     */
    public function execute(): Page
    {
        $this->_initSession();

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('OneMoveTwo_Offers::offers');
        $resultPage->getConfig()->getTitle()->prepend(__('Offer'));
        $resultPage->getConfig()->getTitle()->prepend(__('New Offer'));
        return $resultPage;
    }
}
