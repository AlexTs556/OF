<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\Create;

class LoadBlock extends \Magento\Sales\Controller\Adminhtml\Order\Create\LoadBlock
{
    /**
     * Loading page block
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        //Todo:: checking
        $request = $this->getRequest();
        try {
            $this->_initSession()->_processData();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_reloadQuote();
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->_reloadQuote();
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

        $asJson = $request->getParam('json');
        $block = $request->getParam('block');

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        if ($asJson) {
            $resultPage->addHandle('sales_order_create_load_block_json');
        } else {
            $resultPage->addHandle('sales_order_create_load_block_plain');
        }

        if ($block) {
            $blocks = explode(',', $block);
            if ($asJson && !in_array('message', $blocks)) {
                $blocks[] = 'message';
            }

            foreach ($blocks as $block) {
                $resultPage->addHandle('offers_offer_create_load_block_' . $block);
            }
        }

        $result = $resultPage->getLayout()->renderElement('content');
        //Todo:: checking
        if ($request->getParam('as_js_varname')) {
            $this->_session->setUpdateResult($result);
            return $this->resultRedirectFactory->create()->setPath('sales/*/showUpdateResult');
        }
        return $this->resultRawFactory->create()->setContents($result);
    }
}
