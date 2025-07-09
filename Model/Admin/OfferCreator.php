<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Admin;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Phrase;

readonly class OfferCreator
{
    public function __construct(
        private Session $authSession
    ) {
    }

    /**
     * Get creator of offer
     */
    public function getOfferCreator()
    {
        return $this->authSession->getUser()->getId();
    }
}
