<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api\Data;

interface OfferItemDataInterface extends OfferItemInterface
{
    /**
     * Get item attachments
     *
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemAttachmentInterface[]
     */
    public function getAttachments(): array;

    /**
     * Set item attachments
     *
     * @param \OneMoveTwo\Offers\Api\Data\OfferItemAttachmentInterface[] $attachments
     * @return $this
     */
    public function setAttachments(array $attachments): self;

    /**
     * Get product data (from catalog)
     *
     * @return array|null
     */
    public function getProductData(): ?array;

    /**
     * Set product data
     *
     * @param array $productData
     * @return $this
     */
    public function setProductData(array $productData): self;

    /**
     * Get parsed product options
     *
     * @return array
     */
    public function getParsedProductOptions(): array;

    /**
     * Get parsed additional options
     *
     * @return array
     */
    public function getParsedAdditionalOptions(): array;
}
