<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service\Validation;

use OneMoveTwo\Offers\Api\Data\OfferInterface;
use Magento\Framework\Exception\LocalizedException;

readonly class OfferValidator
{
    /**
     * Validate offer item data
     *
     * @param OfferInterface $offer
     * @throws LocalizedException
     */
    public function validate(OfferInterface $offer): void
    {
        $errors = [];

        if (!empty($errors)) {
            throw new LocalizedException(__(implode(' ', $errors)));
        }
    }
}
