<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer;

use OneMoveTwo\Offers\Controller\Adminhtml\Offer;

class View extends Offer
{
    const string ADMIN_RESOURCE = 'OneMoveTwo_Offers::actions_view';

    public function execute()
    {
        $offer = $this->_initOffer();
        $this->_initSession();
        $this->_initMagentoQuote();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($offer) {
            try {
                $resultPage = $this->_initAction();
                $resultPage->getConfig()->getTitle()->prepend(__('Offer'));
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Exception occurred during order load'));
                $resultRedirect->setPath('sales/order/index');
                return $resultRedirect;
            }
            $resultPage->getConfig()->getTitle()->prepend(sprintf("#%s", $offer->getOfferNumber()));
            return $resultPage;
        }
        $resultRedirect->setPath('offers/*/');
        return $resultRedirect;
    }
}
