<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\View;

use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Catalog\Helper\Product\Composite;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;

class ConfigureOfferItems extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly Item $item,
        private readonly Option $option,
        private readonly Composite $composite,
        private readonly DataObjectFactory $dataObjectFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $configureResult = $this->dataObjectFactory->create();
        try {
            $quoteItemId = (int)$this->getRequest()->getParam('id');
            if (!$quoteItemId) {
                throw new LocalizedException(__('Offer item id is not received.'));
            }

            //use repository
            $quoteItem = $this->item->load($quoteItemId);
            if (!$quoteItem->getId()) {
                throw new LocalizedException(__('Offer item is not loaded.'));
            }


            $configureResult->setOk(true);
            $optionCollection = $this->option->getCollection()->addItemFilter([$quoteItemId]);
            $quoteItem->setOptions($optionCollection->getOptionsByItem($quoteItem));


            //Todo: customer_id
           // $customerId = $this->session->getCustomerId();
            $customerId = 1;

            $configureResult
                ->setBuyRequest($quoteItem->getBuyRequest())
                ->setCurrentStoreId($quoteItem->getStoreId())
                ->setProductId($quoteItem->getProductId())
                ->setCurrentCustomerId($customerId);
        } catch (\Exception $e) {
            $configureResult
                ->setError(true)
                ->setMessage($e->getMessage());
        }

        return $this->composite->renderConfigureResult($configureResult);
    }
}
