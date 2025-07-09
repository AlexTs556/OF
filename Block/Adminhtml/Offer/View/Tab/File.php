<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;
use OneMoveTwo\Offers\Block\Adminhtml\Offer\AbstractOffer;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Admin;
use OneMoveTwo\Offers\Model\Offer\File as FileModel;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\Filesystem\Io\File as FileSystem;
use OneMoveTwo\Offers\Model\Data\Offer;

class File extends AbstractOffer implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'offer/view/details/offer/uploadedfiles.phtml';

    public function __construct(
        private readonly FileModel $fileModel,
        private readonly EncoderInterface $urlEncoder,
        private readonly FileSystem $ioFile,
        private readonly Registry $registry,
        Context $context,
        Admin $adminHelper,
        array $data = []
    ) {
        parent::__construct($registry, $adminHelper, $context, $data);
    }


    /**
     * Retrieve quote model instance
     */
    public function getOffer(): Offer
    {
        return $this->registry->registry('current_offer');
    }

    /**
     * Get Table Title
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle(): \Magento\Framework\Phrase|string
    {
        return __('Offer Files');
    }

    /**
     * Get Tab label
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel(): \Magento\Framework\Phrase|string
    {
        return __('Uploaded Files');
    }

    /**
     * Check if tab can be shown
     *
     * @return bool
     */
    public function canShowTab(): bool
    {
        return true;
    }

    /**
     * Check if tab is hidden
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return false;
    }

    /**
     * Get the uploaded files
     *
     * @return array
     */
    public function getUploadedFiles(): array
    {
        return [];
       // return $this->fileModel->getFileDataFromQuotation();
    }

    /**
     * Get the download url
     *
     * @param string $file
     * @return string
     */
    public function getDownloadUrl(string $file): string
    {
        $file = $this->urlEncoder->encode($file);
        return $this->getUrl('offers/file/download', ['file' => $file]);
    }

    /**
     * Get the delete url
     *
     * @param string $file
     * @return string
     */
    public function getDeleteUrl(string $file): string
    {
        $file = $this->urlEncoder->encode($file);
        $quoteId = $this->getRequest()->getParam('quote_id');

        return $this->getUrl('quotation/file/remove', ['file' => $file, 'quote_id' => $quoteId]);
    }

    /**
     * Get checkbox id
     *
     * @param string $file
     * @return string
     */
    public function getCheckboxId(string $file): bool|string
    {
        return $this->trimFileName($file);
    }

    /**
     * Trim file name
     *
     * @param string $file
     * @return bool|string
     */
    public function trimFileName(string $file): bool|string
    {
        return $this->ioFile->getPathInfo($file)['basename'];
    }

    /**
     * Is checked or not
     *
     * @param string $file
     * @param string $location
     * @return bool
     */
    public function isChecked(string $file, string $location): bool
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        $file = $this->ioFile->getPathInfo($file)['basename'];

        return $this->fileModel->visible($file, $quoteId, $location);
    }

    /**
     * Get action url
     *
     * @return string
     */
    public function getUrlAction(): string
    {
        return $this->getUrl('quotation/file/save');
    }
}
