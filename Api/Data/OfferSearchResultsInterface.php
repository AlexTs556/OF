<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface OfferSearchResultsInterface extends SearchResultsInterface
{
    /**
    * @return OfferInterface[]
    */
    public function getItems(): array;

    /**
    * @param OfferInterface[] $items
    * @return $this
    */
    public function setItems(array $items): self;
}
