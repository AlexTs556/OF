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
use OneMoveTwo\Offers\Service\History\OfferHistoryManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;

class OfferAttachmentManagementService implements OfferAttachmentManagementInterface
{
    public function __construct(
        private readonly OfferAttachmentRepositoryInterface $attachmentRepository,
        private readonly OfferRepositoryInterface $offerRepository,
        private readonly OfferAttachmentInterfaceFactory $attachmentFactory,
        private readonly AttachmentFileManager $fileManager,
        private readonly AttachmentValidator $attachmentValidator,
        private readonly OfferHistoryManager $historyManager,
        private readonly UrlInterface $urlBuilder
    ) {

    }

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

        // Save file
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

    public function updateAttachment(int $attachmentId, ?string $fileName = null, ?int $sortOrder = null): OfferAttachmentInterface
    {
        $attachment = $this->attachmentRepository->getById($attachmentId);

        if ($fileName !== null) {
            $attachment->setFileName($fileName);
        }

        if ($sortOrder !== null) {
            $attachment->setSortOrder($sortOrder);
        }

        $savedAttachment = $this->attachmentRepository->save($attachment);

        // Add to history
        $this->historyManager->addRecord(
            $attachment->getOfferId(),
            'attachment_updated',
            sprintf('Attachment "%s" updated', $attachment->getFileName())
        );

        return $savedAttachment;
    }

    public function removeAttachment(int $attachmentId): bool
    {
        $attachment = $this->attachmentRepository->getById($attachmentId);
        $offerId = $attachment->getOfferId();
        $fileName = $attachment->getFileName();

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
    }

    public function getAttachmentContent(int $attachmentId): string
    {
        $attachment = $this->attachmentRepository->getById($attachmentId);
        return $this->fileManager->getFileContent($attachment->getFilePath());
    }

    public function copyAttachmentToOffer(int $attachmentId, int $targetOfferId): OfferAttachmentInterface
    {
        $originalAttachment = $this->attachmentRepository->getById($attachmentId);

        // Validate target offer exists
        $this->offerRepository->getById($targetOfferId);

        // Copy file
        $newFilePath = $this->fileManager->copyFile(
            $originalAttachment->getFilePath(),
            $targetOfferId,
            $originalAttachment->getFileName()
        );

        // Create new attachment record
        $newAttachment = $this->attachmentFactory->create();
        $newAttachment->setOfferId($targetOfferId);
        $newAttachment->setFilePath($newFilePath);
        $newAttachment->setFileName($originalAttachment->getFileName());
        $newAttachment->setFileType($originalAttachment->getFileType());
        $newAttachment->setSortOrder($originalAttachment->getSortOrder());

        return $this->attachmentRepository->save($newAttachment);
    }

    public function reorderAttachments(int $offerId, array $attachmentOrder): bool
    {
        // Validate offer exists
        $this->offerRepository->getById($offerId);

        foreach ($attachmentOrder as $attachmentId => $sortOrder) {
            try {
                $attachment = $this->attachmentRepository->getById($attachmentId);
                if ($attachment->getOfferId() == $offerId) {
                    $attachment->setSortOrder($sortOrder);
                    $this->attachmentRepository->save($attachment);
                }
            } catch (NoSuchEntityException $e) {
                // Skip non-existent attachments
                continue;
            }
        }

        return true;
    }

    public function getDownloadUrl(int $attachmentId): string
    {
        $attachment = $this->attachmentRepository->getById($attachmentId);

        return $this->urlBuilder->getUrl(
            'onemovetwo_offers/attachment/download',
            ['id' => $attachmentId]
        );
    }
}
