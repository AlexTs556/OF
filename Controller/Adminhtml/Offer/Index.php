<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer;

use OneMoveTwo\Offers\Controller\Adminhtml\Offer;

use Magento\Backend\Model\View\Result\Page;

class Index extends Offer
{
    public function execute(): Page
    {
        /*if ($results = parent::execute()) {
            return $results;
        }*/

        $resultPage = $this->_initAction();
        $resultPage->getConfig()->getTitle()->prepend(__('Offers'));
        return $resultPage;
    }
}
