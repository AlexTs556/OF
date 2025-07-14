<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Controller\Adminhtml\Offer\Attachment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use OneMoveTwo\Offers\Service\OfferAttachmentManagementService;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Delete extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    public function __construct(
        private readonly OfferAttachmentManagementService $attachmentManagement,
        private readonly JsonFactory $jsonFactory,
        private readonly LoggerInterface $logger,
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Delete attachment
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $attachmentId = (int)$this->getRequest()->getParam('attachment_id');

            if (!$attachmentId) {
                throw new LocalizedException(__('Attachment ID is required'));
            }

            // Delete attachment
            $success = $this->attachmentManagement->removeAttachment($attachmentId);

            if ($success) {
                return $result->setData([
                    'success' => true,
                    'message' => __('Attachment deleted successfully')
                ]);
            } else {
                throw new LocalizedException(__('Failed to delete attachment'));
            }

        } catch (\Exception $e) {
            $this->logger->error('Error deleting attachment', [
                'attachment_id' => $attachmentId ?? null,
                'error' => $e->getMessage()
            ]);

            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if user has permission to delete attachments
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('OneMoveTwo_Offers::offer_edit');
    }
}
