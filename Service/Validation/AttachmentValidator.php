<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service\Validation;

use Magento\Framework\Exception\LocalizedException;

class AttachmentValidator
{
    const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv'
    ];

    const int MAX_FILE_SIZE = 10485760; // 10MB

    /**
     * Validate uploaded file
     *
     * @param string $fileContent Base64 encoded content
     * @param string $fileName
     * @param string $fileType
     * @throws LocalizedException
     */
    public function validateFile(string $fileContent, string $fileName, string $fileType): void
    {
        $errors = [];

        // Validate file name
        if (empty($fileName)) {
            $errors[] = __('File name is required.');
        }

        if (strlen($fileName) > 255) {
            $errors[] = __('File name is too long (maximum 255 characters).');
        }

        // Validate file type
        if (!in_array($fileType, self::ALLOWED_MIME_TYPES)) {
            $errors[] = __('File type "%1" is not allowed.', $fileType);
        }

        // Validate file size
        $decodedContent = base64_decode($fileContent);
        if (strlen($decodedContent) > self::MAX_FILE_SIZE) {
            $errors[] = __('File size exceeds maximum allowed size of %1 MB.', self::MAX_FILE_SIZE / 1048576);
        }

        // Validate file extension matches MIME type
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!$this->validateExtensionMimeType($extension, $fileType)) {
            $errors[] = __('File extension does not match file type.');
        }

        if (!empty($errors)) {
            throw new LocalizedException(__(implode(' ', $errors)));
        }
    }

    /**
     * Validate extension matches MIME type
     *
     * @param string $extension
     * @param string $mimeType
     * @return bool
     */
    private function validateExtensionMimeType(string $extension, string $mimeType): bool
    {
        $extensionMimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'csv' => 'text/csv'
        ];

        return isset($extensionMimeMap[$extension]) && $extensionMimeMap[$extension] === $mimeType;
    }
}
