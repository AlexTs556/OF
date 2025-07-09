<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Offer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;

class File
{
    const FILE_DOWNLOAD = 'download';
    const FILE_DELETE = 'delete';
    const CUSTOMER_FOLDER = 'customer';
    const EMAIL_FOLDER = 'email';
    const SHOW_CUSTOMER = 'show_customer';
    const SHOW_EMAIL = 'show_email';
    const DONT_EMAIL = 'dont_email';
    const QUOTATION_FOLDER = 'quotation';
    const QUOTATION_ROOT = '';

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var \Cart2Quote\Quotation\Model\Session
     */
    private $quoteSession;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $fileDriver;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $ioFile;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $backendSessionQuote;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    /**
     * @var \Cart2Quote\Quotation\Helper\FileUpload
     */
    private $fileUploadHelper;

    /**
     * @var \Magento\Downloadable\Helper\Download
     */
    protected $downloadHelper;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Cart2Quote\Quotation\Helper\QuotationTaxHelper
     */
    protected $quotationTaxHelper;

    /**
     * File constructor.
     *
     * @param \Magento\Downloadable\Helper\Download $downloadHelper
     * @param \Cart2Quote\Quotation\Helper\FileUpload $fileUploadHelper
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Backend\Model\Session\Quote $backendSessionQuote
     * @param \Magento\Framework\Filesystem\Io\File $io
     * @param \Cart2Quote\Quotation\Model\Session $quoteSession
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Cart2Quote\Quotation\Helper\QuotationTaxHelper $quotationTaxHelper
     */
    public function __construct(
        \Magento\Downloadable\Helper\Download $downloadHelper,
        \Cart2Quote\Quotation\Helper\FileUpload $fileUploadHelper,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Backend\Model\Session\Quote $backendSessionQuote,
        \Magento\Framework\Filesystem\Io\File $io,
        \Cart2Quote\Quotation\Model\Session $quoteSession,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\App\Request\Http $request,
        \Cart2Quote\Quotation\Helper\QuotationTaxHelper $quotationTaxHelper
    ) {
        $this->downloadHelper = $downloadHelper;
        $this->fileUploadHelper = $fileUploadHelper;
        $this->fileFactory = $fileFactory;
        $this->backendSessionQuote = $backendSessionQuote;
        $this->ioFile = $io;
        $this->fileDriver = $fileDriver;
        $this->quoteSession = $quoteSession;
        $this->filesystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->request = $request;
        $this->quotationTaxHelper = $quotationTaxHelper;
    }

    /**
     * Upload the files
     *
     * @param int $fileAmount
     * @param bool $backend
     * @param int $quoteId
     * @return array
     * @throws \Exception
     */
    public function uploadFiles($fileAmount, $backend = false, $quoteId = null)
    {
        $imagesData = [];
        $allowedExtensions = $this->fileUploadHelper->getAllowedFileExtensions();
        $usedFileTitles = [];

        for ($i = 0; $i < $fileAmount; $i++) {
            $fileTitle = null;
            $fileTitlePost = $this->request->getPost('title_' . $i);
            if (!empty($fileTitlePost)) {
                $fileTitle = $fileTitlePost;
            }

            $uploaderFactory = $this->uploaderFactory->create(['fileId' => 'fileupload_' . $i]);
            $uploaderFactory->setAllowedExtensions($allowedExtensions);
            $uploaderFactory->setAllowRenameFiles(false);
            $uploaderFactory->setFilesDispersion(false);
            if (!empty($fileTitle)) {
                $fileExtention = $uploaderFactory->getFileExtension();
                $orgFileTitle = $fileTitle;

                $existingData = $this->getFileDataFromSession();
                if (!is_array($existingData)) {
                    $existingData = [];
                }

                $counter = 1;
                while (in_array(strtolower($fileTitle), $usedFileTitles)
                    || array_key_exists($fileTitle . '.' . $fileExtention, $existingData)) {
                    $fileTitle = $orgFileTitle . '_' . $counter;
                    $counter++;
                }
                $usedFileTitles[] = strtolower($fileTitle);

                $fileTitle = $fileTitle . '.' . $fileExtention;
            }
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            if ($backend && isset($quoteId)) {
                $destinationPath = $mediaDirectory->getAbsolutePath(
                    self::QUOTATION_FOLDER . DIRECTORY_SEPARATOR . $quoteId . DIRECTORY_SEPARATOR
                );
                $this->fileDriver->createDirectory($destinationPath);
                $imageData = $uploaderFactory->save($destinationPath, $fileTitle);
            } else {
                $destinationPath = $mediaDirectory->getAbsolutePath(
                        self::QUOTATION_FOLDER
                    ) . DIRECTORY_SEPARATOR . 'temp';
                $imageData = $uploaderFactory->save($destinationPath, $fileTitle);
                $fileTitle = null;
                $this->setImageDataToSession($imageData);

            }
            $imagesData[] = $imageData;
        }

        return $imagesData;
    }

    /**
     * Make files which were uploaded by the customer automatically visible to the customer on the frontend
     *
     * @param int $quoteId
     * @throws FileSystemException
     */
    public function setFilesVisibleToCustomer($quoteId)
    {
        if (isset($quoteId)) {
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $files = $this->getFiles($quoteId, '');

            foreach ($files as $source) {
                $destinationDirectory = $mediaDirectory->getAbsolutePath(
                    self::QUOTATION_FOLDER .
                    DIRECTORY_SEPARATOR .
                    $quoteId .
                    DIRECTORY_SEPARATOR .
                    self::CUSTOMER_FOLDER
                );

                if (!$mediaDirectory->isExist($destinationDirectory)) {
                    $this->fileDriver->createDirectory($destinationDirectory);
                }

                $source = $mediaDirectory->getAbsolutePath($source);
                $destination = $mediaDirectory->getAbsolutePath(
                    $destinationDirectory .
                    DIRECTORY_SEPARATOR .
                    $this->ioFile->getPathInfo($source)['basename']
                );
                $this->ioFile->cp($source, $destination);
            }
        }
    }

    /**
     * Remove the file
     *
     * @param string $fileName
     * @return void
     * @throws FileSystemException
     */
    public function removeFile($fileName)
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $path = $mediaDirectory->getAbsolutePath(self::QUOTATION_FOLDER);
        $path .= DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;

        try {
            //delete file
            $this->fileDriver->deleteFile($path . $fileName);

            //update session list of files
            $existingData = $this->getFileDataFromSession();
            unset($existingData[$fileName]);

            $this->quoteSession->setUploadedFile($existingData);
        } catch (\Exception $exception) {
            throw new FileSystemException(
                new \Magento\Framework\Phrase(
                    'The "%1" file can\'t be deleted.',
                    [$fileName]
                )
            );
        }
    }

    /**
     * Set the image data to the session
     *
     * @param array $imageData
     */
    public function setImageDataToSession($imageData)
    {
        $existingData = $this->getFileDataFromSession();

        if (is_array($existingData)) {
            $data = $existingData;
        }

        $data[$imageData['file']] = $imageData;

        $this->quoteSession->setUploadedFile($data);
    }

    /**
     * Get the file data from the session
     *
     * @return array|null
     */
    public function getFileDataFromSession()
    {
        return $this->quoteSession->getUploadedFile();
    }

    /**
     * Save the files of quotation quote
     *
     * @param int $quoteId
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function saveFileQuotationQuote($quoteId)
    {
        $files = $this->getFileDataFromSession();
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $path = $mediaDirectory->getAbsolutePath(
            self::QUOTATION_FOLDER
            . DIRECTORY_SEPARATOR
            . $quoteId
            . DIRECTORY_SEPARATOR
        );
        $this->fileDriver->createDirectory($path);

        foreach ($files as $file) {
            $source = $file['path'] . DIRECTORY_SEPARATOR . $file['file'];
            if (!str_contains($source, DIRECTORY_SEPARATOR . self::QUOTATION_FOLDER . DIRECTORY_SEPARATOR)) {
                //don't allow files that aren't in the quotation folder
                continue;
            }

            $destination = $path . $file['file'];
            $this->ioFile->mv($source, $destination);
            $this->setFilesVisibleToCustomer($quoteId);
        }
    }

    /**
     * Get the file data from quotation
     *
     * @return array
     */
    public function getFileDataFromQuotation()
    {
        $quoteId = $this->backendSessionQuote->getQuotationQuoteId();
        if ($quoteId) {
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            if ($mediaDirectory->isExist(self::QUOTATION_FOLDER . DIRECTORY_SEPARATOR . $quoteId)) {
                $files = $mediaDirectory->read(self::QUOTATION_FOLDER . DIRECTORY_SEPARATOR . $quoteId);

                return array_filter($files, function ($file) use ($mediaDirectory) {
                    return !$mediaDirectory->isDirectory($file);
                });
            }
        }
    }

    /**
     * Get the file action
     *
     * @param string $fileName
     * @param string $action
     * @return \Magento\Framework\App\ResponseInterface|string
     * @throws \Exception
     */
    public function fileAction($fileName, $action)
    {
        $fileName = str_replace('..', '', $fileName);
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        $newfileName = $mediaDirectory->getRelativePath($fileName);
        $path = $mediaDirectory->getAbsolutePath($fileName);

        if ($mediaDirectory->isFile($fileName)) {

            if ($action == self::FILE_DOWNLOAD) {
                $magentoVersion = $this->quotationTaxHelper->getMagentoVersion();
                if (version_compare($magentoVersion, '2.4.7', '>=')) {
                    $content = [
                        'type' => 'filename',
                        'value' => $fileName,
                        'rm' => false
                    ];
                } else {
                    $content = $this->ioFile->read($path);
                }

                return $this->fileFactory->create(
                    $newfileName,
                    $content,
                    DirectoryList::MEDIA
                );
            } elseif ($action == self::FILE_DELETE) {
                $fileName = $this->ioFile->getPathInfo($path)['basename'];
                $this->fileDriver->deleteFile($path);

                return $fileName;
            }

        } else {
            throw new \Exception((string)new \Magento\Framework\Phrase('File not found'));
        }
    }

    /**
     * Add files to frontend and mail
     *
     * @param array $files
     * @param string $quoteId
     * @param string $location
     * @return array|bool
     * @throws FileSystemException
     */
    public function addTo($files, $quoteId, $location)
    {
        $messages = [];
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $path = $mediaDirectory->getAbsolutePath(
            self::QUOTATION_FOLDER . DIRECTORY_SEPARATOR . $quoteId . DIRECTORY_SEPARATOR
        );
        $customerPath = $path . $location;

        if (!$mediaDirectory->isExist($customerPath)) {
            $this->fileDriver->createDirectory($customerPath);
        }

        foreach ($files as $fileName => $show) {
            $destination = $customerPath . DIRECTORY_SEPARATOR . $fileName;

            if ($show == self::SHOW_CUSTOMER) {

                if (!$mediaDirectory->isExist($destination)) {
                    $source = $path . $fileName;
                    $result = $this->ioFile->cp($source, $destination);

                    if ($result) {
                        $messages[] = __('File %1 added to frontend', $fileName);
                    }
                }
            } elseif ($show == self::SHOW_EMAIL) {

                if (!$mediaDirectory->isExist($destination)) {
                    $source = $path . $fileName;
                    $result = $this->ioFile->cp($source, $destination);

                    if ($result) {
                        $messages[] = __('File %1 added to email', $fileName);
                    }
                }
            } elseif ($mediaDirectory->isExist($destination)) {
                $result = $this->fileDriver->deleteFile($destination);

                if ($result) {
                    if ($show == self::DONT_EMAIL) {
                        $messages[] = __('File %1 removed from proposal email', $fileName);
                    } else {
                        $messages[] = __('File %1 removed from frontend', $fileName);
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * Copy the files to a new quote
     *
     * @param array $files
     * @param int $quoteId
     * @param string $location
     * @throws FileSystemException
     */
    public function copyFilesToNewQuote($files, $quoteId, $location)
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        foreach ($files as $source) {

            if (!$location == "") {
                $destinationDirectory = $mediaDirectory->getAbsolutePath(
                    self::QUOTATION_FOLDER . DIRECTORY_SEPARATOR . $quoteId . DIRECTORY_SEPARATOR . $location
                );
            } else {
                $destinationDirectory = $mediaDirectory->getAbsolutePath(
                    self::QUOTATION_FOLDER . DIRECTORY_SEPARATOR . $quoteId . $location
                );
            }

            if (!$mediaDirectory->isExist($destinationDirectory)) {
                $this->fileDriver->createDirectory($destinationDirectory);
            }

            $source = $mediaDirectory->getAbsolutePath($source);
            $destination = $mediaDirectory->getAbsolutePath(
                $destinationDirectory . DIRECTORY_SEPARATOR . $this->ioFile->getPathInfo($source)['basename']
            );
            $this->ioFile->cp($source, $destination);
        }
    }

    /**
     * Set the visibility of files
     *
     * @param string $file
     * @param string $quoteId
     * @param string $location
     * @return bool
     */
    public function visible($file, $quoteId, $location)
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $path = $mediaDirectory->getAbsolutePath(
            self::QUOTATION_FOLDER . DIRECTORY_SEPARATOR . $quoteId . DIRECTORY_SEPARATOR
        );
        $customerPath = $path . $location;
        $destination = $customerPath . DIRECTORY_SEPARATOR . $file;

        if ($mediaDirectory->isExist($destination)) {
            return true;
        }
        return false;
    }

    /**
     * Get the files
     *
     * @param string $quoteId
     * @param string $location
     * @return array|null
     */
    public function getFiles($quoteId, $location)
    {
        if ($quoteId) {
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $path = self::QUOTATION_FOLDER .
                DIRECTORY_SEPARATOR .
                $quoteId .
                DIRECTORY_SEPARATOR .
                $location;

            if ($mediaDirectory->isExist($path)) {
                return $mediaDirectory->read($path);
            }
        }

        return null;
    }
}
