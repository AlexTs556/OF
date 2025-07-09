<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api\Data;

/**
 * Offer Attachment interface
 */
interface OfferAttachmentInterface
{
    /**
     * const for keys of data array. Identical to the name of the getter in snake case
     */
    public const string CACHE_TAG = 'offer_attachment';
    public const string ATTACHMENT_ID = 'attachment_id';
    public const string OFFER_ID = 'offer_id';
    public const string FILE_PATH = 'file_path';
    public const string FILE_NAME = 'file_name';
    public const string FILE_TYPE = 'file_type';
    public const string SORT_ORDER = 'sort_order';
    public const string CREATED_AT = 'created_at';
    public const string UPDATED_AT = 'updated_at';

    /**
     * Get attachment ID
     *
     * @return int|null
     */
    public function getAttachmentId(): ?int;

    /**
     * Set attachment ID
     *
     * @param int $attachmentId
     * @return $this
     */
    public function setAttachmentId(int $attachmentId): self;

    /**
     * Get offer ID
     *
     * @return int
     */
    public function getOfferId(): int;

    /**
     * Set offer ID
     *
     * @param int $offerId
     * @return $this
     */
    public function setOfferId(int $offerId): self;

    /**
     * Get file path
     *
     * @return string
     */
    public function getFilePath(): string;

    /**
     * Set file path
     *
     * @param string $filePath
     * @return $this
     */
    public function setFilePath(string $filePath): self;

    /**
     * Get file name
     *
     * @return string
     */
    public function getFileName(): string;

    /**
     * Set file name
     *
     * @param string $fileName
     * @return $this
     */
    public function setFileName(string $fileName): self;

    /**
     * Get file type
     *
     * @return string
     */
    public function getFileType(): string;

    /**
     * Set file type
     *
     * @param string $fileType
     * @return $this
     */
    public function setFileType(string $fileType): self;

    /**
     * Get sort order
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Set sort order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): self;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?string $createdAt): self;

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at
     *
     * @param string|null $updatedAt
     * @return $this
     */
    public function setUpdatedAt(?string $updatedAt): self;
}
