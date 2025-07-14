<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service\Validation;

use OneMoveTwo\Offers\Api\Data\OfferItemInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Api\ProductRepositoryInterface;

readonly class OfferItemValidator
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ){
    }

    /**
     * Validate offer item data
     *
     * @param OfferItemInterface $item
     * @throws LocalizedException
     */
    public function validate(OfferItemInterface $item): void
    {
        $errors = [];

        // Required fields
        if (!$item->getProductId()) {
            $errors[] = __('Product ID is required.');
        }

        if (!$item->getName()) {
            $errors[] = __('Product name is required.');
        }

        if (!$item->getSku()) {
            $errors[] = __('SKU is required.');
        }

        // Quantity validation
        if ($item->getQty() <= 0) {
            $errors[] = __('Quantity must be greater than 0.');
        }

        // Price validation
        if ($item->getPrice() < 0) {
            $errors[] = __('Price cannot be negative.');
        }

        // Discount validation
        if ($item->getDiscountPercent() < 0 || $item->getDiscountPercent() > 100) {
            $errors[] = __('Discount percent must be between 0 and 100.');
        }

        if ($item->getDiscountAmount() && $item->getDiscountAmount() < 0) {
            $errors[] = __('Discount amount cannot be negative.');
        }

        // Validate product exists
        if ($item->getProductId()) {
            try {
                $this->productRepository->getById($item->getProductId());
            } catch (\Exception $e) {
                $errors[] = __('Product with ID %1 does not exist.', $item->getProductId());
            }
        }

        // Validate JSON options
        if ($item->getProductOptions()) {
            $options = json_decode($item->getProductOptions(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = __('Product options must be valid JSON.');
            }
        }

        if ($item->getAdditionalOptions()) {
            $options = json_decode($item->getAdditionalOptions(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = __('Additional options must be valid JSON.');
            }
        }

        if (!empty($errors)) {
            throw new LocalizedException(__(implode(' ', $errors)));
        }
    }
}
