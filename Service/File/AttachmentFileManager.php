<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service\File;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Exception\LocalizedException;

readonly class AttachmentFileManager
{
    public function __construct(
        private Filesystem    $filesystem,
        private DirectoryList $directoryList
    ) {
    }

    /**
     * Save file and return file path
     *
     * @param int $offerId
     * @param string $fileContent Base64 encoded content
     * @param string $fileName
     * @return string File path relative to media directory
     * @throws LocalizedException
     */
    public function saveFile(int $offerId, string $fileContent, string $fileName): string
    {
        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            // Create directory structure
            $offerDir = 'offers/' . $offerId . '/attachments';
            $mediaDirectory->create($offerDir);

            // Generate unique filename
            $pathInfo = pathinfo($fileName);
            $uniqueFileName = $pathInfo['filename'] . '_' . date('Ymd_His') . '.' . $pathInfo['extension'];
            $filePath = $offerDir . '/' . $uniqueFileName;

            // Decode and save file
            $decodedContent = base64_decode($fileContent);
            $mediaDirectory->writeFile($filePath, $decodedContent);

            return $filePath;

        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not save file: %1', $e->getMessage()));
        }
    }

    /**
     * Get file content as base64
     *
     * @param string $filePath
     * @return string
     * @throws LocalizedException
     */
    public function getFileContent(string $filePath): string
    {
        try {
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            if (!$mediaDirectory->isExist($filePath)) {
                throw new LocalizedException(__('File does not exist: %1', $filePath));
            }

            $content = $mediaDirectory->readFile($filePath);
            return base64_encode($content);

        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not read file: %1', $e->getMessage()));
        }
    }

    /**
     * Delete file
     *
     * @param string $filePath
     * @return bool
     * @throws LocalizedException
     */
    public function deleteFile(string $filePath): bool
    {
        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            if ($mediaDirectory->isExist($filePath)) {
                $mediaDirectory->delete($filePath);
            }

            return true;

        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not delete file: %1', $e->getMessage()));
        }
    }

    /**
     * Copy file to another offer
     *
     * @param string $sourcePath
     * @param int $targetOfferId
     * @param string $fileName
     * @return string New file path
     * @throws LocalizedException
     */
    public function copyFile(string $sourcePath, int $targetOfferId, string $fileName): string
    {
        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            if (!$mediaDirectory->isExist($sourcePath)) {
                throw new LocalizedException(__('Source file does not exist: %1', $sourcePath));
            }

            // Read source file content
            $content = $mediaDirectory->readFile($sourcePath);

            // Save to new location
            return $this->saveFile($targetOfferId, base64_encode($content), $fileName);

        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not copy file: %1', $e->getMessage()));
        }
    }

    /**
     * Get absolute file path for download
     *
     * @param string $filePath
     * @return string
     */
    public function getAbsolutePath(string $filePath): string
    {
        $mediaPath = $this->directoryList->getPath(DirectoryList::MEDIA);
        return $mediaPath . '/' . $filePath;
    }
}
