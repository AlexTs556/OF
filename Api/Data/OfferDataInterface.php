<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api\Data;

interface OfferDataInterface extends OfferInterface
{
    /**
     * Get offer items
     *
     * @return \OneMoveTwo\Offers\Api\Data\OfferItemInterface[]
     */
    public function getItems(): array;

    /**
     * Set offer items
     *
     * @param \OneMoveTwo\Offers\Api\Data\OfferItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items): self;

    /**
     * Get offer attachments
     *
     * @return \OneMoveTwo\Offers\Api\Data\OfferAttachmentInterface[]
     */
    public function getAttachments(): array;

    /**
     * Set offer attachments
     *
     * @param \OneMoveTwo\Offers\Api\Data\OfferAttachmentInterface[] $attachments
     * @return $this
     */
    public function setAttachments(array $attachments): self;

    /**
     * Get offer history
     *
     * @return \OneMoveTwo\Offers\Api\Data\OfferHistoryInterface[]
     */
    public function getHistory(): array;

    /**
     * Set offer history
     *
     * @param \OneMoveTwo\Offers\Api\Data\OfferHistoryInterface[] $history
     * @return $this
     */
    public function setHistory(array $history): self;
}
