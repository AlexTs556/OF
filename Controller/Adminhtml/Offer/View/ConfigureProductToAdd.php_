<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\View;

use OneMoveTwo\Offers\Controller\Adminhtml\Offer\View;
use Magento\Backend\App\Action\Context;
use \Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use OneMoveTwo\Offers\Model\OfferRepository;
use Magento\Backend\Model\Session\Quote;
use Magento\Catalog\Helper\Product\Composite;

class ConfigureProductToAdd extends View
{
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        LoggerInterface $logger,
        OfferRepository $offerRepository,
        private readonly Quote $sessionQuote,
        private readonly Composite $compositeHelper,
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $resultPageFactory,
            $logger,
            $sessionQuote,
            $offerRepository
        );
    }

    public function execute()
    {
        $productId = (int)$this->getRequest()->getParam('id');
        $configureResult = new \Magento\Framework\DataObject();
        $configureResult->setOk(true);
        $configureResult->setProductId($productId);
        $configureResult->setCurrentStoreId($this->sessionQuote->getStore()->getId());
        $configureResult->setCurrentCustomerId($this->sessionQuote->getCustomerId());
        return $this->compositeHelper->renderConfigureResult($configureResult);
    }
}
