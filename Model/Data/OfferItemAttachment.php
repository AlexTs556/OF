<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Data;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use OneMoveTwo\Offers\Api\Data\OfferItemAttachmentInterface;
use OneMoveTwo\Offers\Model\ResourceModel\OfferItemAttachment as OfferItemAttachmentResource;

/**
 * Offer Item Attachment Model
 */
class OfferItemAttachment extends AbstractModel implements OfferItemAttachmentInterface, IdentityInterface
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OfferItemAttachmentResource::class);
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG . '_' . $this->getAttachmentId()];
    }

    /**
     * @inheritDoc
     */
    public function getAttachmentId(): ?int
    {
        $attachmentId = $this->getData(self::ATTACHMENT_ID);
        return $attachmentId ? (int)$attachmentId : null;
    }

    /**
     * @inheritDoc
     */
    public function setAttachmentId(int $attachmentId): OfferItemAttachmentInterface
    {
        return $this->setData(self::ATTACHMENT_ID, $attachmentId);
    }

    /**
     * @inheritDoc
     */
    public function getOfferItemsId(): int
    {
        return (int)$this->getData(self::OFFER_ITEMS_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOfferItemsId(int $offerItemsId): OfferItemAttachmentInterface
    {
        return $this->setData(self::OFFER_ITEMS_ID, $offerItemsId);
    }

    /**
     * @inheritDoc
     */
    public function getFilePath(): string
    {
        return (string)$this->getData(self::FILE_PATH);
    }

    /**
     * @inheritDoc
     */
    public function setFilePath(string $filePath): OfferItemAttachmentInterface
    {
        return $this->setData(self::FILE_PATH, $filePath);
    }

    /**
     * @inheritDoc
     */
    public function getFileName(): string
    {
        return (string)$this->getData(self::FILE_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setFileName(string $fileName): OfferItemAttachmentInterface
    {
        return $this->setData(self::FILE_NAME, $fileName);
    }

    /**
     * @inheritDoc
     */
    public function getFileType(): string
    {
        return (string)$this->getData(self::FILE_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setFileType(string $fileType): OfferItemAttachmentInterface
    {
        return $this->setData(self::FILE_TYPE, $fileType);
    }

    /**
     * @inheritDoc
     */
    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    /**
     * @inheritDoc
     */
    public function setSortOrder(int $sortOrder): OfferItemAttachmentInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(?string $createdAt): OfferItemAttachmentInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(?string $updatedAt): OfferItemAttachmentInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
