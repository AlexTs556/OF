<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service;

use Magento\Store\Model\StoreManagerInterface;
use OneMoveTwo\Offers\Model\Config\ConfigProvider;

readonly class OfferNumberGeneratorService
{
    public function __construct(
        private ConfigProvider $configProvider,
        private StoreManagerInterface $storeManager
    ) {}

    /**
     * Generate offer number based on template and offer data
     *
     * @param int $version Offer version (1, 2, 3, etc.)
     * @param int|null $storeId Store ID (if null, current store will be used)
     * @return string Generated offer number or empty string if auto-generation is disabled
     */
    public function generateOfferNumber(int $version, ?int $storeId = null): string
    {
        if (!$this->configProvider->isAutoGenerateNumberEnabled() || !$template = $this->configProvider->getOfferNumberTemplate()) {
            return '';
        }

        $placeholders = $this->preparePlaceholders($version, $storeId);
        return $this->replacePlaceholders($template, $placeholders);
    }

    /**
     * Get prefix from configuration
     */
    private function getPrefix(): string
    {
        return $this->configProvider->getOfferPrefix();
    }

    /**
     * Prepare all placeholders for replacement
     */
    private function preparePlaceholders(int $version, ?int $storeId): array
    {
        return [
            '{prefix}' => $this->getPrefix(),
            '{store}' => $this->getStoreId($storeId),
            '{datetime}' => $this->generateDateTime(),
            '{date}' => $this->generateDate(),
            '{version}' => (string) $version,
        ];
    }

    /**
     * Get store ID (current or specified)
     */
    private function getStoreId(?int $storeId): string
    {
        if ($storeId !== null) {
            return (string) $storeId;
        }

        try {
            return (string) $this->storeManager->getStore()->getId();
        } catch (\Exception $e) {
            return '0'; // Fallback to admin store
        }
    }

    /**
     * Generate datetime string with milliseconds
     */
    private function generateDateTime(): string
    {
        $microtime = microtime(true);
        $milliseconds = sprintf('%03d', ($microtime - floor($microtime)) * 1000);

        return date('YmdHis') . $milliseconds;
    }

    /**
     * Generate date string (without time)
     */
    private function generateDate(): string
    {
        return date('Ymd');
    }

    /**
     * Replace placeholders in template
     */
    private function replacePlaceholders(string $template, array $placeholders): string
    {
        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template
        );
    }
}
