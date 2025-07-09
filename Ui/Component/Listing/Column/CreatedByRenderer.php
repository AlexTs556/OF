<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\User\Model\UserFactory;

class CreatedByRenderer extends Column
{
    public function __construct(
        private readonly UserFactory $userFactory,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $userId = $item['admin_creator_id'] ?? null;

                if ($userId) {
                    try {
                        $user = $this->userFactory->create()->load($userId);
                        $item['admin_creator_id'] = $user->getName();
                    } catch (\Exception $e) {
                        $item['admin_creator_id'] = __('Unknown');
                    }
                }
            }
        }

        return $dataSource;
    }
}
