<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\Save;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Helper\Product;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use OneMoveTwo\Offers\Model\Data\OfferFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\AddressFactory;
use Magento\Quote\Model\CustomerManagement;
use OneMoveTwo\Offers\Model\Admin\OfferCreator;
use Cart2Quote\Quotation\Model\QuoteCartManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\Model\View\Result\Redirect;
use OneMoveTwo\Offers\Model\Data\Offer;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Api\Data\OfferAttachmentInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferAttachmentRepositoryInterface;
use OneMoveTwo\Offers\Api\Data\OfferHistoryInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferHistoryRepositoryInterface;

use Magento\Framework\Controller\Result\JsonFactory;


class Info extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    public function __construct(
        private readonly OfferRepositoryInterface $offerRepository,
        private readonly JsonFactory $jsonFactory,
        private readonly OfferAttachmentInterfaceFactory $offerAttachmentFactory,
        private readonly OfferHistoryInterfaceFactory $offerHistoryInterfaceFactory,
        private readonly OfferAttachmentRepositoryInterface $offerAttachmentRepository,
        private readonly OfferHistoryRepositoryInterface $historyRepository,
        Context $context
    ) {
        parent::__construct(
            $context
        );
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $data = $this->getRequest()->getPostValue();

            $offer = $this->offerRepository->getById((int)$data['offer_id']);
            $offer->setOfferName($data['offer_name']);
            $offer->setExpiryDate($data['expiry_date']);
            $this->offerRepository->save($offer);

            if (isset($data['comment']) && $data['comment']) {
                $offerHistory = $this->offerHistoryInterfaceFactory->create();
                $offerHistory->setOfferId((int)$offer->getId());
                $offerHistory->setStatus('New');
                $offerHistory->setComment($data['comment']);
                $offerHistory->setIsCustomerNotified(false);
                $offerHistory->setVisibleOnStorefront(false);
                $offerHistory->setCreatedByName((string)$offer->getAdminCreatorId());
                $this->historyRepository->save($offerHistory);
            }



            // Ваша логика сохранения данных
            // ...

            return $result->setData([
                'success' => true,
                'message' => __('Offer saved successfully'),
                'redirect_url' => $this->getUrl('*/*/index')
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


}
