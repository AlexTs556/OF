<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service;

use OneMoveTwo\Offers\Api\OfferAttachmentManagementInterface;
use OneMoveTwo\Offers\Api\OfferAttachmentRepositoryInterface;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Api\Data\OfferAttachmentInterface;
use OneMoveTwo\Offers\Api\Data\OfferAttachmentInterfaceFactory;
use OneMoveTwo\Offers\Service\File\AttachmentFileManager;
use OneMoveTwo\Offers\Service\Validation\AttachmentValidator;
use OneMoveTwo\Offers\Service\History\OfferHistoryManagementService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

readonly class OfferAttachmentManagementService implements OfferAttachmentManagementInterface
{
    public function __construct(
        private OfferAttachmentRepositoryInterface $attachmentRepository,
        private OfferRepositoryInterface           $offerRepository,
        private OfferAttachmentInterfaceFactory    $attachmentFactory,
        private AttachmentFileManager              $fileManager,
        private AttachmentValidator                $attachmentValidator,
        private OfferHistoryManagementService      $historyManager,
        private FileUploadService                  $fileUploadService,
        private UrlInterface                       $urlBuilder,
        private LoggerInterface                    $logger
    ) {}

    /**
     * Process multiple file uploads for an offer
     *
     * @param int $offerId
     * @param array $uploadedFiles Files from $_FILES
     * @param array $filesInfo Additional file information from frontend
     * @return OfferAttachmentInterface[]
     * @throws LocalizedException
     */
    public function processFileUploads(int $offerId, array $uploadedFiles, array $filesInfo = []): array
    {
        try {
            // Use FileUploadService to handle file uploads and create attachment objects
            $attachmentObjects = $this->fileUploadService->processUploadedFiles($uploadedFiles, $filesInfo);

            $savedAttachments = [];

            foreach ($attachmentObjects as $index => $attachment) {
                // Set offer ID for each attachment
                $attachment->setOfferId($offerId);

                // Save attachment to database
                $savedAttachment = $this->attachmentRepository->save($attachment);
                $savedAttachments[] = $savedAttachment;

                // Log successful attachment
                $this->logger->info('Attachment saved successfully', [
                    'offer_id' => $offerId,
                    'attachment_id' => $savedAttachment->getAttachmentId(),
                    'file_name' => $savedAttachment->getFileName()
                ]);
            }

            // Add to history if attachments were processed
            if (!empty($savedAttachments)) {
                $fileNames = array_map(fn($att) => $att->getFileName(), $savedAttachments);
                $this->historyManager->addRecord(
                    $offerId,
                    'attachments_added',
                    sprintf('Attachments uploaded: %s', implode(', ', $fileNames))
                );
            }

            return $savedAttachments;

        } catch (\Exception $e) {
            $this->logger->error('Failed to process file uploads for offer', [
                'offer_id' => $offerId,
                'error' => $e->getMessage(),
                'files_count' => count($uploadedFiles)
            ]);

            throw new LocalizedException(
                __('Failed to process file uploads: %1', $e->getMessage())
            );
        }
    }

    /**
     * Upload single attachment from file content
     */
    public function uploadAttachment(
        int $offerId,
        string $fileContent,
        string $fileName,
        string $fileType,
        int $sortOrder = 0
    ): OfferAttachmentInterface {
        // Validate offer exists
        $offer = $this->offerRepository->getById($offerId);

        // Validate file
        $this->attachmentValidator->validateFile($fileContent, $fileName, $fileType);

        // Save file using existing file manager
        $filePath = $this->fileManager->saveFile($offerId, $fileContent, $fileName);

        // Create attachment record
        $attachment = $this->attachmentFactory->create();
        $attachment->setOfferId($offerId);
        $attachment->setFilePath($filePath);
        $attachment->setFileName($fileName);
        $attachment->setFileType($fileType);
        $attachment->setSortOrder($sortOrder);

        $savedAttachment = $this->attachmentRepository->save($attachment);

        // Add to history
        $this->historyManager->addRecord(
            $offerId,
            'attachment_added',
            sprintf('Attachment "%s" uploaded', $fileName)
        );

        return $savedAttachment;
    }

    /**
     * Get all attachments for an offer with download URLs
     */
    public function getOfferAttachmentsWithUrls(int $offerId): array
    {
        // This method should be implemented in your repository to get attachments by offer ID
        // For now, assuming you have this method or will implement it
        $attachments = $this->attachmentRepository->getByOfferId($offerId);

        $result = [];
        foreach ($attachments as $attachment) {
            $result[] = [
                'attachment' => $attachment,
                'download_url' => $this->getDownloadUrl($attachment->getAttachmentId()),
                'preview_url' => $this->getPreviewUrl($attachment->getAttachmentId()),
                'is_image' => $this->isImageFile($attachment->getFileType())
            ];
        }

        return $result;
    }

    /**
     * Check if file is an image
     */
    private function isImageFile(string $fileType): bool
    {
        $imageTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp'
        ];

        return in_array($fileType, $imageTypes);
    }

    /**
     * Get preview URL for images
     */
    public function getPreviewUrl(int $attachmentId): ?string
    {
        try {
            $attachment = $this->attachmentRepository->getById($attachmentId);

            if ($this->isImageFile($attachment->getFileType())) {
                return $this->urlBuilder->getUrl(
                    'onemovetwo_offers/attachment/preview',
                    ['id' => $attachmentId]
                );
            }

            return null;
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    public function removeAttachment(int $attachmentId): bool
    {
        $attachment = $this->attachmentRepository->getById($attachmentId);
        $offerId = $attachment->getOfferId();
        $fileName = $attachment->getFileName();

        try {
            // Delete file
            $this->fileManager->deleteFile($attachment->getFilePath());

            // Delete record
            $result = $this->attachmentRepository->deleteById($attachmentId);

            if ($result) {
                // Add to history
                $this->historyManager->addRecord(
                    $offerId,
                    'attachment_removed',
                    sprintf('Attachment "%s" removed', $fileName)
                );
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove attachment', [
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage()
            ]);

            throw new LocalizedException(
                __('Failed to remove attachment: %1', $e->getMessage())
            );
        }
    }

    public function getAttachmentContent(int $attachmentId): string
    {
        $attachment = $this->attachmentRepository->getById($attachmentId);
        return $this->fileManager->getFileContent($attachment->getFilePath());
    }

    public function getDownloadUrl(int $attachmentId): string
    {
        return $this->urlBuilder->getUrl(
            'onemovetwo_offers/attachment/download',
            ['id' => $attachmentId]
        );
    }
}
