<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Converter;

use Magento\Quote\Api\Data\CartItemInterface;
use OneMoveTwo\Offers\Api\Data\OfferItemInterface;
use OneMoveTwo\Offers\Api\Data\OfferItemInterfaceFactory;

readonly class QuoteToOfferItem
{
    public function __construct(
        private OfferItemInterfaceFactory $offerItemFactory
    ) {
    }

    /**
     * Convert quote item to offer item
     *
     * @param CartItemInterface $quoteItem
     * @param int $offerId
     * @return OfferItemInterface
     */
    public function convert(CartItemInterface $quoteItem, int $offerId): OfferItemInterface
    {
        $offerItem = $this->offerItemFactory->create();

        // Маппинг полей из quote item в offer item
        $offerItem->setOfferId($offerId)
            ->setProductId((int) $quoteItem->getProductId())
            ->setSku($quoteItem->getSku() ?? '')
            ->setName($quoteItem->getName() ?? '')
            ->setQty($quoteItem->getQty() ?? 0.0)
            ->setPrice($quoteItem->getPrice() ?? 0.0)
            ->setBasePrice($quoteItem->getBasePrice())
            ->setDiscountPercent($this->calculateDiscountPercent($quoteItem))
            ->setDiscountAmount($quoteItem->getDiscountAmount())
            ->setRowTotal($quoteItem->getRowTotal() ?? 0.0)
            ->setIsOptional(false) // По умолчанию
            ->setHasCustomOptions($this->hasCustomOptions($quoteItem))
            ->setProductOptions($this->getProductOptions($quoteItem))
            ->setAdditionalOptions($this->getAdditionalOptions($quoteItem));

        return $offerItem;
    }

    /**
     * Calculate discount percent
     *
     * @param CartItemInterface $quoteItem
     * @return float|null
     */
    private function calculateDiscountPercent(CartItemInterface $quoteItem): ?float
    {
        $price = $quoteItem->getPrice();
        $basePrice = $quoteItem->getBasePrice();

        if ($basePrice && $price && $basePrice > $price) {
            return round((($basePrice - $price) / $basePrice) * 100, 2);
        }

        return null;
    }

    /**
     * Check if quote item has custom options
     *
     * @param CartItemInterface $quoteItem
     * @return bool
     */
    private function hasCustomOptions(CartItemInterface $quoteItem): bool
    {
        $options = $quoteItem->getOptions();
        return !empty($options);
    }

    /**
     * Get product options as JSON string
     *
     * @param CartItemInterface $quoteItem
     * @return string|null
     */
    private function getProductOptions(CartItemInterface $quoteItem): ?string
    {
        $options = $quoteItem->getOptions();
        if (empty($options)) {
            return null;
        }

        $productOptions = [];
        foreach ($options as $option) {
            if ($option->getCode() === 'info_buyRequest') {
                continue; // Skip buy request info
            }
            $productOptions[] = [
                'code' => $option->getCode(),
                'value' => $option->getValue()
            ];
        }

        return !empty($productOptions) ? json_encode($productOptions) : null;
    }

    /**
     * Get additional options as JSON string
     *
     * @param CartItemInterface $quoteItem
     * @return string|null
     */
    private function getAdditionalOptions(CartItemInterface $quoteItem): ?string
    {
        // Здесь можно добавить логику для дополнительных опций
        // например, из customizable options или bundle options
        return null;
    }
}
