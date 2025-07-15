<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

class OfferStatuses extends Value
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @throws LocalizedException
     */
    public function beforeSave(): OfferStatuses
    {
        $value = $this->getValue();

        if (is_array($value)) {
            // Validate draft status exists
            $hasDraft = false;
            foreach ($value as $status) {
                if (isset($status['code']) && $status['code'] === 'draft') {
                    $hasDraft = true;
                    break;
                }
            }

            if (!$hasDraft) {
                throw new LocalizedException(__('Draft status is required and cannot be deleted.'));
            }

            // Validate required fields
            foreach ($value as $index => $status) {
                if (empty($status['code'])) {
                    throw new LocalizedException(__('Status code is required for row %1.', $index + 1));
                }
                if (empty($status['label'])) {
                    throw new LocalizedException(__('Status label is required for row %1.', $index + 1));
                }

                if (!preg_match('/^[a-z0-9_]+$/', $status['code'])) {
                    throw new LocalizedException(__('Status code "%1" can only contain lowercase letters, numbers and underscores.', $status['code']));
                }
            }

            // Check for duplicate codes
            $codes = array_column($value, 'code');
            if (count($codes) !== count(array_unique($codes))) {
                throw new LocalizedException(__('Duplicate status codes are not allowed.'));
            }

            $this->setValue($this->serializer->serialize($value));
        }

        return parent::beforeSave();
    }

    protected function _afterLoad(): OfferStatuses
    {
        $value = $this->getValue();
        if ($value) {
            try {
                $this->setValue($this->serializer->unserialize($value));
            } catch (\Exception $e) {
                $this->setValue('');
            }
        }

        return parent::_afterLoad();
    }
}
