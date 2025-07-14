<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\Attachment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use OneMoveTwo\Offers\Service\OfferAttachmentManagementService;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Load extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    public function __construct(
        private readonly OfferAttachmentManagementService $attachmentManagement,
        private readonly OfferRepositoryInterface $offerRepository,
        private readonly JsonFactory $jsonFactory,
        private readonly LoggerInterface $logger,
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Load existing attachments for offer
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $offerId = (int)$this->getRequest()->getParam('offer_id');

            if (!$offerId) {
                throw new LocalizedException(__('Offer ID is required'));
            }

            // Validate offer exists
            $offer = $this->offerRepository->getById($offerId);

            // Get attachments with URLs
            $attachmentsData = $this->attachmentManagement->getOfferAttachmentsWithUrls($offerId);

            // Format data for frontend
            $attachments = [];
            foreach ($attachmentsData as $attachmentData) {
                $attachment = $attachmentData['attachment'];
                $attachments[] = [
                    'id' => $attachment->getAttachmentId(),
                    'name' => $attachment->getFileName(),
                    'type' => $attachment->getFileType(),
                    'size' => $this->getFileSize($attachment->getFilePath()),
                    'download_url' => $attachmentData['download_url'],
                    'preview_url' => $attachmentData['preview_url'],
                    'is_image' => $attachmentData['is_image'],
                    'sort_order' => $attachment->getSortOrder(),
                    'created_at' => $attachment->getCreatedAt()
                ];
            }

            return $result->setData([
                'success' => true,
                'attachments' => $attachments,
                'count' => count($attachments)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error loading offer attachments', [
                'offer_id' => $offerId ?? null,
                'error' => $e->getMessage()
            ]);

            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get file size in bytes (you might need to implement this based on your file storage)
     */
    private function getFileSize(string $filePath): int
    {
        // This is a placeholder - implement based on your file storage system
        // For example, if files are stored in media directory:
        // $fullPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($filePath);
        // return file_exists($fullPath) ? filesize($fullPath) : 0;

        return 0; // Return 0 for now, implement actual logic
    }

    /**
     * Check if user has permission to view offer attachments
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('OneMoveTwo_Offers::offer_view');
    }
}
