<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use OneMoveTwo\Offers\Api\Data\OfferAttachmentInterfaceFactory;
use OneMoveTwo\Offers\Api\Data\OfferAttachmentInterface;
use Psr\Log\LoggerInterface;

class FileUploadService
{
    private const UPLOAD_DIR = 'offers/attachments';
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    public function __construct(
        private readonly UploaderFactory $uploaderFactory,
        private readonly Filesystem $filesystem,
        private readonly OfferAttachmentInterfaceFactory $attachmentFactory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Process multiple uploaded files
     *
     * @param array $files $_FILES array
     * @param array $attachmentsInfo Additional file information
     * @return OfferAttachmentInterface[]
     * @throws LocalizedException
     */
    public function processUploadedFiles(array $files, array $attachmentsInfo = []): array
    {
        $attachments = [];

        if (!is_array($files)) {
            return $attachments;
        }

        foreach ($files as $index => $fileData) {
            if ($this->isValidUpload($fileData)) {
                try {
                    $fileInfo = $attachmentsInfo[$index] ?? [];
                    $attachment = $this->uploadFile($fileData, $fileInfo);
                    if ($attachment) {
                        $attachments[] = $attachment;
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error processing file upload', [
                        'file_index' => $index,
                        'error' => $e->getMessage(),
                        'file_data' => $this->sanitizeFileData($fileData)
                    ]);

                    // Throw exception to stop processing if any file fails
                    throw new LocalizedException(
                        __('Failed to upload file "%1": %2',
                            $fileInfo['name'] ?? $fileData['name'] ?? 'unknown',
                            $e->getMessage()
                        )
                    );
                }
            }
        }

        return $attachments;
    }

    /**
     * Upload single file and create attachment
     *
     * @param array $fileData File data from $_FILES
     * @param array $fileInfo Additional file information
     * @return OfferAttachmentInterface|null
     * @throws LocalizedException
     */
    public function uploadFile(array $fileData, array $fileInfo = []): ?OfferAttachmentInterface
    {
        // Validate file
        $this->validateFile($fileData, $fileInfo);

        try {
            // Create uploader
            $uploader = $this->uploaderFactory->create(['fileId' => $fileData]);
            $uploader->setAllowedExtensions(self::ALLOWED_EXTENSIONS);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $uploader->setAllowCreateFolders(true);

            // Get upload directory
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $uploadPath = $mediaDirectory->getAbsolutePath(self::UPLOAD_DIR);

            // Ensure directory exists
            if (!$mediaDirectory->isDirectory(self::UPLOAD_DIR)) {
                $mediaDirectory->create(self::UPLOAD_DIR);
            }

            // Upload file
            $result = $uploader->save($uploadPath);

            if (!$result || !isset($result['file'])) {
                throw new LocalizedException(__('File upload failed'));
            }

            // Create attachment object
            $attachment = $this->attachmentFactory->create();
            $attachment->setFilePath(self::UPLOAD_DIR . '/' . ltrim($result['file'], '/'));
            $attachment->setFileName($fileInfo['name'] ?? $result['name']);
            $attachment->setFileType($this->determineFileType($fileData, $fileInfo));
            $attachment->setSortOrder($this->determineSortOrder($fileInfo));

            return $attachment;

        } catch (\Exception $e) {
            $this->logger->error('File upload exception', [
                'error' => $e->getMessage(),
                'file_info' => $fileInfo,
                'file_data' => $this->sanitizeFileData($fileData)
            ]);

            throw new LocalizedException(
                __('Upload failed for file "%1": %2',
                    $fileInfo['name'] ?? $fileData['name'] ?? 'unknown',
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Validate uploaded file
     *
     * @param array $fileData
     * @param array $fileInfo
     * @throws LocalizedException
     */
    private function validateFile(array $fileData, array $fileInfo): void
    {
        // Check upload error
        if (!isset($fileData['error']) || $fileData['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = $this->getUploadErrorMessage($fileData['error'] ?? UPLOAD_ERR_NO_FILE);
            throw new LocalizedException(__('Upload error: %1', $errorMessage));
        }

        // Check file size
        $fileSize = $fileData['size'] ?? 0;
        if ($fileSize > self::MAX_FILE_SIZE) {
            $maxSizeMB = self::MAX_FILE_SIZE / 1024 / 1024;
            throw new LocalizedException(
                __('File size (%1 MB) exceeds maximum allowed size (%2 MB)',
                    round($fileSize / 1024 / 1024, 2),
                    $maxSizeMB
                )
            );
        }

        // Check file extension
        $fileName = $fileInfo['name'] ?? $fileData['name'] ?? '';
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new LocalizedException(
                __('File type "%1" is not allowed. Allowed types: %2',
                    $extension,
                    implode(', ', self::ALLOWED_EXTENSIONS)
                )
            );
        }

        // Check MIME type
        $mimeType = $fileData['type'] ?? '';
        if (!$this->isAllowedMimeType($mimeType)) {
            throw new LocalizedException(
                __('MIME type "%1" is not allowed', $mimeType)
            );
        }
    }

    /**
     * Check if upload is valid
     */
    private function isValidUpload(array $fileData): bool
    {
        return isset($fileData['error']) && $fileData['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Check if MIME type is allowed
     */
    private function isAllowedMimeType(string $mimeType): bool
    {
        $allowedMimes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        return in_array($mimeType, $allowedMimes);
    }

    /**
     * Determine file type for storage
     */
    private function determineFileType(array $fileData, array $fileInfo): string
    {
        // Use file info type if available, otherwise use uploaded type
        return $fileInfo['type'] ?? $fileData['type'] ?? 'application/octet-stream';
    }

    /**
     * Determine sort order
     */
    private function determineSortOrder(array $fileInfo): int
    {
        return (int)($fileInfo['sort_order'] ?? 0);
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error'
        };
    }

    /**
     * Sanitize file data for logging
     */
    private function sanitizeFileData(array $fileData): array
    {
        return [
            'name' => $fileData['name'] ?? null,
            'type' => $fileData['type'] ?? null,
            'size' => $fileData['size'] ?? null,
            'error' => $fileData['error'] ?? null
        ];
    }
}
