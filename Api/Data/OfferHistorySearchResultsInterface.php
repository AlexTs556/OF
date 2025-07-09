<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for offer history search results
 */
interface OfferHistorySearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get offer history list
     *
     * @return OfferHistoryInterface[]
     */
    public function getItems(): array;

    /**
     * Set offer history list
     *
     * @param OfferHistoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items): self;
}
