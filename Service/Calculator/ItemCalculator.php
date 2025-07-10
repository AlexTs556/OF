<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service\Calculator;

use OneMoveTwo\Offers\Api\Data\OfferItemInterface;

class ItemCalculator
{
    /**
     * Calculate item totals (row total, discount amount, etc.)
     *
     * @param OfferItemInterface $item
     * @return void
     */
    public function calculateItemTotals(OfferItemInterface $item): void
    {
        $baseTotal = $item->getPrice() * $item->getQty();

        // Calculate discount amount if percentage is set
        if ($item->getDiscountPercent() > 0) {
            $discountAmount = ($baseTotal * $item->getDiscountPercent()) / 100;
            $item->setDiscountAmount($discountAmount);
        }

        // Calculate row total
        $discountAmount = $item->getDiscountAmount() ?? 0;
        $rowTotal = $baseTotal - $discountAmount;

        $item->setRowTotal($rowTotal);

        // Set base price if not set
        if (!$item->getBasePrice()) {
            $item->setBasePrice($item->getPrice());
        }
    }
}
