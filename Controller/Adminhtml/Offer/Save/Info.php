<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\Save;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory as MediaUploaderFactory;
use OneMoveTwo\Offers\Api\OfferManagementInterface;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Api\Data\OfferAttachmentInterfaceFactory;
use OneMoveTwo\Offers\Api\Data\OfferHistoryInterfaceFactory;
use OneMoveTwo\Offers\Api\OfferHistoryRepositoryInterface;
use Psr\Log\LoggerInterface;

class Info extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    private const UPLOAD_DIR = 'offers/attachments';

    public function __construct(
        private readonly OfferManagementInterface $offerManagement,
        private readonly OfferRepositoryInterface $offerRepository,
        private readonly \OneMoveTwo\Offers\Service\OfferAttachmentManagementService $attachmentManagement,
        private readonly JsonFactory $jsonFactory,
        private readonly OfferHistoryInterfaceFactory $offerHistoryFactory,
        private readonly OfferHistoryRepositoryInterface $historyRepository,
        private readonly LoggerInterface $logger,
        Context $context
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $data = $this->getRequest()->getPostValue();

            if (!isset($data['offer_id']) || !$data['offer_id']) {
                throw new LocalizedException(__('Offer ID is required'));
            }

            // Get existing offer
            $offer = $this->offerRepository->getById((int)$data['offer_id']);

            // Update offer basic data
            $this->updateOfferData($offer, $data);

            // First save the offer using API (without attachments for now)
            $updatedOffer = $this->offerManagement->updateOffer($offer, [], []);

            // Then process attachments separately
            $processedAttachments = [];
            $files = $this->getRequest()->getFiles('attachments');
            if ($files && is_array($files) && !empty($files)) {
                $attachmentsInfo = [];
                if (isset($data['attachments_info'])) {
                    $attachmentsInfo = json_decode($data['attachments_info'], true) ?: [];
                }

                $processedAttachments = $this->attachmentManagement->processFileUploads(
                    $updatedOffer->getEntityId(),
                    $files,
                    $attachmentsInfo
                );
            }

            // Add history comment if provided
            if (!empty($data['comment'])) {
                $this->addHistoryComment($updatedOffer, $data['comment']);
            }

            // Send email if requested
            if (!empty($data['offer_email'])) {
                //$this->offerManagement->sendOfferEmail($updatedOffer->getEntityId());
            }

            $continueEdit = isset($data['back']) && $data['back'] === 'continue';

            return $result->setData([
                'success' => true,
                'message' => __('Offer saved successfully'),
                'offer_id' => $updatedOffer->getEntityId(),
                'attachments_count' => count($processedAttachments),
                'redirect_url' => $continueEdit ? null : $this->getUrl('*/*/index')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error saving offer: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $data ?? [],
                'offer_id' => $data['offer_id'] ?? null
            ]);

            return $result->setData([
                'success' => false,
                'message' => __('Error saving offer: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Update offer basic data
     */
    private function updateOfferData($offer, array $data): void
    {
        if (isset($data['offer_name'])) {
            $offer->setOfferName($data['offer_name']);
        }

        if (isset($data['expiry_date'])) {
            $offer->setExpiryDate($data['expiry_date']);
        }

        // Handle offer number generation
        if (isset($data['offer_number_auto_generate']) && $data['offer_number_auto_generate']) {
            // Auto-generate offer number
            $offer->setOfferNumber($this->generateOfferNumber());
        } elseif (isset($data['offer_number'])) {
            // Use provided offer number
            $offer->setOfferNumber($data['offer_number']);
        }
    }

    /**
     * Generate unique offer number
     */
    private function generateOfferNumber(): string
    {
        // Implement your offer number generation logic
        // For example: OFFER-YYYY-MM-DD-XXXXX
        $date = date('Y-m-d');
        $random = str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);

        return "OFFER-{$date}-{$random}";
    }

    /**
     * Add history comment
     */
    private function addHistoryComment($offer, string $comment): void
    {
        $offerHistory = $this->offerHistoryFactory->create();
        $offerHistory->setOfferId((int)$offer->getEntityId());
        $offerHistory->setStatus($offer->getStatus() ?? 'Updated');
        $offerHistory->setComment($comment);
        $offerHistory->setIsCustomerNotified(false);
        $offerHistory->setVisibleOnStorefront(false);
        $offerHistory->setCreatedByName($this->getAdminUserName());

        $this->historyRepository->save($offerHistory);
    }

    /**
     * Get current admin user name
     */
    private function getAdminUserName(): string
    {
        $adminUser = $this->_auth->getUser();
        return $adminUser ? $adminUser->getFirstname() . ' ' . $adminUser->getLastname() : 'System';
    }

    /**
     * Check if user has permission to save offers
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('OneMoveTwo_Offers::offer_save');
    }
}
