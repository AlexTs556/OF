<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\Create\Shipping;

class Address extends \Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Address
{
    protected function _construct(): void
    {
        parent::_construct();

        //add customerAddressCollection if it isn't set
        if (!$this->hasData('customerAddressCollection')) {
            if (class_exists(\Magento\Customer\Model\ResourceModel\Address\Collection::class)) {
                /** @var \Magento\Customer\Model\ResourceModel\Address\Collection $addressCollection */
                $addressCollection = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Customer\Model\ResourceModel\Address\Collection::class);

                $this->setData('customerAddressCollection', $addressCollection);
            }
        }

        //add customerAddressFormatter if it isn't set
        if (!$this->hasData('customerAddressFormatter')) {
            if (class_exists(\Magento\Sales\ViewModel\Customer\AddressFormatter::class)) {
                /** @var \Magento\Sales\ViewModel\Customer\AddressFormatter $addressFormatter */
                $addressFormatter = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Sales\ViewModel\Customer\AddressFormatter::class);

                $this->setData('customerAddressFormatter', $addressFormatter);
            }
        }

        //add customerAddressCollectionAttributeFilter if it isn't set
        if (!$this->hasData('customerAddressCollectionAttributeFilter')) {
            if (class_exists(\Magento\Sales\ViewModel\Customer\Address\AddressAttributeFilter::class)) {
                /** @var \Magento\Sales\ViewModel\Customer\Address\AddressAttributeFilter $addressAttributeFilter */
                $addressAttributeFilter = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Sales\ViewModel\Customer\Address\AddressAttributeFilter::class);

                $this->setData('customerAddressCollectionAttributeFilter', $addressAttributeFilter);
            }
        }
    }
}
