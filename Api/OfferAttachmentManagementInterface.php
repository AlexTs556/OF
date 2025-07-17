<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api;

use OneMoveTwo\Offers\Api\Data\OfferAttachmentInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface OfferAttachmentManagementInterface
{
    /**
     * Upload and attach file to offer
     *
     * @param int $offerId
     * @param string $fileContent Base64 encoded file content
     * @param string $fileName Original file name
     * @param string $fileType MIME type
     * @param int $sortOrder
     * @return \OneMoveTwo\Offers\Api\Data\OfferAttachmentInterface
     * @throws LocalizedException
     */
    public function uploadAttachment(
        int $offerId,
        string $fileContent,
        string $fileName,
        string $fileType,
        int $sortOrder = 0
    ): OfferAttachmentInterface;

    /**
     * Remove attachment and delete file
     *
     * @param int $attachmentId
     * @return bool
     * @throws LocalizedException
     */
    public function removeAttachment(int $attachmentId): bool;

    /**
     * Get attachment file content
     *
     * @param int $attachmentId
     * @return string Base64 encoded file content
     * @throws NoSuchEntityException
     */
    public function getAttachmentContent(int $attachmentId): string;

    /**
     * Get attachment download URL
     *
     * @param int $attachmentId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDownloadUrl(int $attachmentId): string;
}
