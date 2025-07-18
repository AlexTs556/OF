<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use OneMoveTwo\Offers\Model\Config\ConfigProvider;

readonly class OfferStatuses implements ArrayInterface
{
    public function __construct(private ConfigProvider $configProvider)
    {
    }

    public function toOptionArray(): array
    {
        $statuses = $this->configProvider->getOfferStatuses();
        $options = [];

        foreach ($statuses as $status) {
            $options[] = [
                'value' => $status['code'],
                'label' => $status['label']
            ];
        }

        return $options;
    }
}
