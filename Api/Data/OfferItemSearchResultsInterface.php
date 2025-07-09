<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface OfferItemSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get offer items list
     *
     * @return OfferItemInterface[]
     */
    public function getItems(): array;

    /**
     * Set offer items list
     *
     * @param OfferItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items): self;
}
