<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Data;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use OneMoveTwo\Offers\Api\Data\OfferInterface;
use OneMoveTwo\Offers\Api\Data\OfferItemAttachmentInterface;
use OneMoveTwo\Offers\Model\ResourceModel\Offer as OfferResource;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Exception;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Catalog\Model\Product\Type\AbstractType;
use OneMoveTwo\Offers\Model\ResourceModel\OfferAttachment\CollectionFactory as AttachmentCollectionFactory;

class Offer extends AbstractModel implements OfferInterface, IdentityInterface
{
    /**
     * Cache for loaded attachments
     * @var OfferItemAttachmentInterface[]|null
     */
    private ?array $attachments = null;


    public function __construct(
        private readonly CollectionFactory $quoteItemCollectionFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ManagerInterface $messageManager,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly Quote $magentoQuote,
        private readonly ObjectFactory $objectFactory,
        private readonly AttachmentCollectionFactory $attachmentCollectionFactory,
        private readonly CartInterfaceFactory $quoteFactory,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }



    protected function _construct(): void
    {
        $this->_init(OfferResource::class);
    }

    /**
     * After load processing - automatically load attachments
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        if ($this->getId()) {
            // Load attachments automatically
            $this->loadAttachments();
        }

        return $this;
    }

    /**
     * Get offer item attachments
     *
     * @return OfferItemAttachmentInterface[]
     */
    public function getAttachments(): array
    {
        if ($this->attachments === null) {
            $this->loadAttachments();
        }
        return $this->attachments;
    }

    /**
     * Load offer item attachments
     *
     * @return void
     */
    private function loadAttachments(): void
    {
        if (!$this->getId()) {
            $this->attachments = [];
            return;
        }

        $collection = $this->attachmentCollectionFactory->create();
        $collection->addFieldToFilter('offer_id', $this->getId())
            ->setOrder('sort_order', 'ASC');

        $this->attachments = $collection->getItems();
    }

    /**
     * Set attachments and clear cache
     *
     * @param OfferItemAttachmentInterface[] $attachments
     * @return $this
     */
    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    /**
     * Clear loaded attachments cache
     *
     * @return $this
     */
    public function clearAttachmentsCache(): self
    {
        $this->attachments = null;
        return $this;
    }

    /**
     * Check if item has attachments
     *
     * @return bool
     */
    public function hasAttachments(): bool
    {
        return count($this->getAttachments()) > 0;
    }

    /**
     * Get attachments count
     *
     * @return int
     */
    public function getAttachmentsCount(): int
    {
        return count($this->getAttachments());
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG . '_' . $this->getEntityId()];
    }

    public function getEntityId(): ?int
    {
        return $this->getData(self::ENTITY_ID) ? (int)$this->getData(self::ENTITY_ID) : null;
    }

    public function getOfferNumber(): string
    {
        return (string)$this->getData(self::OFFER_NUMBER);
    }

    public function setOfferNumber(string $offerNumber): OfferInterface
    {
        return $this->setData(self::OFFER_NUMBER, $offerNumber);
    }

    public function getOfferName(): string
    {
        return (string)$this->getData(self::OFFER_NAME);
    }

    public function setOfferName(string $offerName): OfferInterface
    {
        return $this->setData(self::OFFER_NAME, $offerName);
    }

    public function getQuoteId(): ?int
    {
        return $this->getData(self::QUOTE_ID) ? (int)$this->getData(self::QUOTE_ID) : null;
    }

    public function setQuoteId(?int $quoteId): OfferInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    public function getOrderId(): ?int
    {
        return $this->getData(self::ORDER_ID) ? (int)$this->getData(self::ORDER_ID) : null;
    }

    public function setOrderId(?int $orderId): OfferInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    public function getCustomerId(): ?int
    {
        return $this->getData(self::CUSTOMER_ID) ? (int)$this->getData(self::CUSTOMER_ID) : null;
    }

    public function setCustomerId(?int $customerId): OfferInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getCustomerIsGuest(): bool
    {
        return (bool)$this->getData(self::CUSTOMER_IS_GUEST);
    }

    public function setCustomerIsGuest(bool $isGuest): OfferInterface
    {
        return $this->setData(self::CUSTOMER_IS_GUEST, $isGuest);
    }

    public function getCustomerEmail(): ?string
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    public function setCustomerEmail(?string $email): OfferInterface
    {
        return $this->setData(self::CUSTOMER_EMAIL, $email);
    }

    public function getCustomerName(): ?string
    {
        return $this->getData(self::CUSTOMER_NAME);
    }

    public function setCustomerName(?string $name): OfferInterface
    {
        return $this->setData(self::CUSTOMER_NAME, $name);
    }

    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    public function setStatus(string $status): OfferInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getVersion(): int
    {
        return (int)$this->getData(self::VERSION);
    }

    public function setVersion(int $version): OfferInterface
    {
        return $this->setData(self::VERSION, $version);
    }

    public function getParentOfferId(): ?int
    {
        return $this->getData(self::PARENT_OFFER_ID) ? (int)$this->getData(self::PARENT_OFFER_ID) : null;
    }

    public function setParentOfferId(?int $parentId): OfferInterface
    {
        return $this->setData(self::PARENT_OFFER_ID, $parentId);
    }

    public function getAdminCreatorId(): int
    {
        return (int)$this->getData(self::ADMIN_CREATOR_ID);
    }

    public function setAdminCreatorId(int $adminId): OfferInterface
    {
        return $this->setData(self::ADMIN_CREATOR_ID, $adminId);
    }

    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    public function setStoreId(int $storeId): OfferInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getSubtotal(): float
    {
        return (float)$this->getData(self::SUBTOTAL);
    }

    public function setSubtotal(float $subtotal): OfferInterface
    {
        return $this->setData(self::SUBTOTAL, $subtotal);
    }

    public function getDiscountAmount(): float
    {
        return (float)$this->getData(self::DISCOUNT_AMOUNT);
    }

    public function setDiscountAmount(float $amount): OfferInterface
    {
        return $this->setData(self::DISCOUNT_AMOUNT, $amount);
    }

    public function getShippingAmount(): float
    {
        return (float)$this->getData(self::SHIPPING_AMOUNT);
    }

    public function setShippingAmount(float $amount): OfferInterface
    {
        return $this->setData(self::SHIPPING_AMOUNT, $amount);
    }

    public function getTaxAmount(): float
    {
        return (float)$this->getData(self::TAX_AMOUNT);
    }

    public function setTaxAmount(float $amount): OfferInterface
    {
        return $this->setData(self::TAX_AMOUNT, $amount);
    }

    public function getGrandTotal(): float
    {
        return (float)$this->getData(self::GRAND_TOTAL);
    }

    public function setGrandTotal(float $total): OfferInterface
    {
        return $this->setData(self::GRAND_TOTAL, $total);
    }

    public function getPrepaymentAmount(): float
    {
        return (float)$this->getData(self::PREPAYMENT_AMOUNT);
    }

    public function setPrepaymentAmount(float $amount): OfferInterface
    {
        return $this->setData(self::PREPAYMENT_AMOUNT, $amount);
    }

    public function getPrepaymentPercent(): float
    {
        return (float)$this->getData(self::PREPAYMENT_PERCENT);
    }

    public function setPrepaymentPercent(float $percent): OfferInterface
    {
        return $this->setData(self::PREPAYMENT_PERCENT, $percent);
    }

    public function getItemsCount(): int
    {
        return (int)$this->getData(self::ITEMS_COUNT);
    }

    public function setItemsCount(int $count): OfferInterface
    {
        return $this->setData(self::ITEMS_COUNT, $count);
    }

    public function getItemsQty(): float
    {
        return (float)$this->getData(self::ITEMS_QTY);
    }

    public function setItemsQty(float $qty): OfferInterface
    {
        return $this->setData(self::ITEMS_QTY, $qty);
    }

    public function getExpiryDate(): ?string
    {
        return $this->getData(self::EXPIRY_DATE);
    }

    public function setExpiryDate(?string $date): OfferInterface
    {
        return $this->setData(self::EXPIRY_DATE, $date);
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $date): OfferInterface
    {
        return $this->setData(self::CREATED_AT, $date);
    }

    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $date): OfferInterface
    {
        return $this->setData(self::UPDATED_AT, $date);
    }

    public function getItemsCollection($useCache = true)
    {
        return $this->getMagentoQuote()->getItemsCollection($useCache);
    }

    public function getMagentoQuote(): CartInterface
    {
        $quoteId = $this->getQuoteId();
        try {
            $quote = $this->cartRepository->get($quoteId);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $quote = $this->quoteFactory->create();
        }

        return $quote;
    }


    public function addItems(array $products): static
    {
        foreach ($products as $productId => $productData) {
            try {
                $product = $this->productRepository->getById($productId, false, $this->getStoreId());

                if (isset($productData['qty'])) {
                    $request = $this->objectFactory->create(['qty' => $productData['qty']]);
                }

                $item = $this->getMagentoQuote()->addProduct(
                    $product,
                    $request,
                    AbstractType::PROCESS_MODE_FULL
                );

                $item->save();
            } catch (LocalizedException|Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $this;
    }


    public function getStatusHistoryCollection()
    {

        return [];
    }
}
