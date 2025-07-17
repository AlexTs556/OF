<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\View\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
use OneMoveTwo\Offers\Api\Data\OfferInterface;

class OfferInfo extends Template implements TabInterface
{

    public function __construct(
        private readonly Registry $registry,
        private readonly AuthSession $authSession,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getTabLabel(): \Magento\Framework\Phrase|string
    {
        return __('Offer Information');
    }
    /**
     * Get Tab title
     */
    public function getTabTitle(): \Magento\Framework\Phrase|string
    {
        return __('Offer Information');
    }

    /**
     * Check if tab can be shown
     */
    public function canShowTab(): true
    {
        return true;
    }

    /**
     * Check if tab is hidden
     */
    public function isHidden(): false
    {
        return false;
    }

    /**
     * @throws LocalizedException
     */
    private function getQuote()
    {
        if ($this->registry->registry('current_quote')) {
            return $this->registry->registry('current_quote');
        }

        throw new LocalizedException(__('We can\'t get the quote instance right now.'));
    }

    /**
     * @throws LocalizedException
     */
    public function getOffer(): OfferInterface
    {
        if ($this->registry->registry('current_offer')) {
            return $this->registry->registry('current_offer');
        }

        throw new LocalizedException(__('We can\'t get the offer instance right now.'));
    }

    public function getCustomerName()
    {
        return $this->getQuote()->getCustomer()->getFirstname() . ' ' . $this->getQuote()->getCustomer()->getLastname();
    }

    public function getCurrentAdminName()
    {
        $admin = $this->authSession->getUser();

        return $admin->getUserName();
    }

    /*public function getSaveInfoUrl(): string
    {
        return $this->getUrl('offers/offer/save_info');
    }*/

    public function getLoadBlockUrl(int $offerId): string
    {
        return $this->getUrl('offers/offer_view/loadBlock', ['entity_id' => $offerId]);
    }

    public function getOfferAttachments(): array
    {
        $attachments = $this->getOffer()->getAttachments();

        return array_map(function ($attachment) {
            return $attachment->getData();
        }, $attachments);
    }

}
