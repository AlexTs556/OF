<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Service;

use Cart2Quote\Quotation\Model\Quote;
use Magento\Framework\App\Helper\Context;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\Rule;

class OfferRule extends \Magento\Framework\App\Helper\AbstractHelper
{
    const RULE_NAME = 'Proposal for quote #%1';

    const RULE_DESCRIPTION = 'Cart2Quote Custom Discount Coupon';

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\SalesRule\Model\Rule\Condition\AddressFactory
     */
    protected $addressConditionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    protected $customerGroupCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $context;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule
     */
    protected $ruleResource;

    /**
     * @var \Cart2Quote\Quotation\Helper\CollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * QuoteRule constructor.
     *
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\SalesRule\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\SalesRule\Model\Rule\Condition\AddressFactory $addressConditionFactory
     * @param \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroupCollection
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\ResourceModel\Rule $ruleResource,
        \Magento\SalesRule\Model\Rule\Condition\AddressFactory $addressConditionFactory,
        \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroupCollection,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        Context $context
    ) {
        parent::__construct($context);
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->ruleFactory = $ruleFactory;
        $this->addressConditionFactory = $addressConditionFactory;
        $this->customerGroupCollection = $customerGroupCollection;
        $this->storeManager = $storeManager;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->context = $context;
        $this->ruleResource = $ruleResource;
    }

    /**
     * Create quote rule
     *
     * @param Quote $quote
     * @param float $amount
     * @param bool $percentage
     * @param string|null $couponCode
     *
     * @return \Magento\SalesRule\Model\Rule
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function createQuoteRule(Quote $quote, $amount, $percentage = false, $couponCode = null)
    {
        /** @var \Magento\SalesRule\Model\Rule $rule */
        $rule = $this->ruleFactory->create();
        $actionConditionQuoteId = $this->addressConditionFactory->create()
            ->setAttribute('quote_id')
            ->setOperator('==')
            ->setValue($quote->getId())
            ->setType(\Magento\SalesRule\Model\Rule\Condition\Address::class);

        $customerGroupIds = $this->customerGroupCollection->addFieldToSelect('customer_group_id')->load()->getAllIds();
        $rule->setIsActive(true)
            ->setName($this->getRuleName($quote))
            ->setDescription(self::RULE_DESCRIPTION)
            ->setCouponType(Rule::COUPON_TYPE_SPECIFIC)
            ->setCouponCode($couponCode ?? $rule->getCouponCodeGenerator()->generateCode())
            ->setUsesPerCoupon(1)
            ->setUsesPerCustomer(1)
            ->setCustomerGroupIds($customerGroupIds)
            ->setFromDate(date('Y-m-d'))
            ->setToDate(null)
            ->setProductIds(null)
            ->setSortOrder(null)
            ->setSimpleAction($percentage ? Rule::BY_PERCENT_ACTION : Rule::CART_FIXED_ACTION)
            ->setDiscountAmount($amount)
            ->setDiscountQty(null)
            ->setDiscountStep(null)
            ->setApplyToShipping(false)
            ->setStopRulesProcessing(false)
            ->setTimesUsed(null)
            ->setIsRss(false)
            ->setWebsiteIds($this->storeManager->getStore(true)->getWebsiteId())
            ->setStoreLabels(['Proposal']);

        $rule->getConditions()->addCondition($actionConditionQuoteId);
        $validateResult = $rule->validateData($this->dataObjectFactory->create($rule->getData()));
        if ($validateResult !== true) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Coupon rule not validated'));
        }

        $this->ruleResource->save($rule);

        return $rule;
    }

    /**
     * Get rule name
     *
     * @param Quote $quote
     *
     * @return \Magento\Framework\Phrase
     */
    protected function getRuleName(Quote $quote)
    {
        return __($this::RULE_NAME, $quote->getId());
    }

    /**
     * Clean quote rules
     *
     * @param \Cart2Quote\Quotation\Model\Quote $quote
     */
    public function cleanQuoteRules(Quote $quote)
    {
        foreach ($this->getRulesForQuote($quote) as $rule) {
            $this->ruleResource->delete($rule);
        }
    }

    /**
     * Rule exists for quote
     *
     * @param \Cart2Quote\Quotation\Model\Quote $quote
     *
     * @return bool
     */
    public function ruleExistsForQuote(Quote $quote)
    {
        if ($quote->getCouponCode()) {
            /** @var Collection $ruleCollection */
            $ruleCollection = $this->ruleCollectionFactory->create();
            $ruleCollection->addFieldToFilter('name', $this->getRuleName($quote));

            return $this->getRulesForQuote($quote)->count() > 0;
        }

        return false;
    }

    /**
     * Get rules for quote
     *
     * @param \Cart2Quote\Quotation\Model\Quote $quote
     *
     * @return \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    public function getRulesForQuote(Quote $quote)
    {
        /** @var Collection $ruleCollection */
        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('name', $this->getRuleName($quote));

        return $ruleCollection;
    }
}
