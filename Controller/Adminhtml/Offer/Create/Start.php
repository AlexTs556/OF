<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\Create;

use Magento\Backend\Model\View\Result\Redirect;

class Start extends \Magento\Sales\Controller\Adminhtml\Order\Create\Start
{
    /**
     * Start offer create action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $this->_getSession()->clearStorage();
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('offers/*', ['customer_id' => $this->getRequest()->getParam('customer_id')]);
    }
}
