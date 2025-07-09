<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Form\Container;

class View extends Container
{
    /**
     * @var string
     */
    protected $_blockGroup = 'OneMoveTwo_Offers';

    /**
     * @var array
     */
    protected $_editableStatus = [
        'proposal_sent',
        'proposal_expired',
        'ordered'
    ];

    public function __construct(
        private readonly Context $context,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getHeaderText()
    {
        $extQuoteId = $this->getQuote()->getExtQuoteId();
        if ($extQuoteId) {
            $extQuoteId = '[' . $extQuoteId . '] ';
        } else {
            $extQuoteId = '';
        }

        return __(
            'Quote # %1 %2 | %3'
            );

        /*return __(
            'Quote # %1 %2 | %3',
            $this->getQuote()->getRealQuoteId(),
            $extQuoteId,
            $this->formatDate(
                $this->_localeDate->date(new \DateTime($this->getQuote()->getQuotationCreatedAt())),
                \IntlDateFormatter::MEDIUM,
                true
            )
        );*/
    }

    public function getOffer()
    {
        return $this->registry->registry('current_offer');
    }

    /**
     * Hold URL getter
     *
     * @return string
     */
    public function getHoldUrl()
    {
        return $this->getUrl('offers/*/hold');
    }

    /**
     * URL getter
     *
     * @param string $params
     * @param array $params2
     * @return string
     */
    public function getUrl($params = '', $params2 = [])
    {
        //$params2['quote_id'] = $this->getQuoteId();

        return parent::getUrl($params, $params2);
    }

    /**
     * Retrieve Quote Identifier
     *
     * @return int
     */
    public function getQuoteId()
    {
        return $this->getQuote() ? $this->getQuote()->getId() : null;
    }

    /**
     * Comment URL getter
     *
     * @return string
     */
    public function getCommentUrl()
    {
        return $this->getUrl('offers/*/comment');
    }

    /**
     * Return back url for view grid
     *
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->getQuote() && $this->getQuote()->getBackUrl()) {
            return $this->getQuote()->getBackUrl();
        }

        return $this->getUrl('offers/*/');
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'entity_id';
        $this->_controller = 'adminhtml_offer';
        $this->_mode = 'view';

        parent::_construct();

        $this->setId('offers_offer_view');
        $offer = $this->getOffer();

        if (!$offer) {
            return;
        }

        $this->removeButton('save');
        $this->removeButton('reset');

        if (in_array($offer->getStatus(), $this->_editableStatus)) {
            $this->addButton(
                'cancel',
                [
                    'label' => __('Cancel Offer'),
                    'class' => 'cancel',
                    'onclick' => 'offer.cancel(" ' . $this->getCancelUrl() . ' ");'
                ],
                1
            );

            $this->addButton(
                'edit',
                [
                    'label' => __('Edit Offer'),
                    'class' => 'edit',
                    'onclick' => 'offer.edit("' . $this->getEditUrl() . '");'
                ],
                1
            );
        }

        $this->addButton(
            'duplicate',
            [
                'label' => __('New Version'),
                'class' => 'secondary',
                //'onclick' => 'offer.duplicate("' . $this->getDuplicateUrl() . '");',
                'onclick' => 'location.reload();',
            ],
            1
        );

        /*$this->addButton(
            'save_offer',
            [
                'label' => __('Save!!!!'),
                'class' => 'save primary',
                'onclick' => 'offer.submit();'
            ],
            1
        );*/
    }

    /**
     * Edit URL getter
     *
     * @return string
     */
    public function getEditUrl()
    {
        return $this->getUrl('quotation/quote/edit');
    }

    /**
     * Cancel URL getter
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl('quotation/quote/cancel');
    }

    /**
     * Duplicate URL getter
     *
     * @return string
     */
    public function getDuplicateUrl()
    {
        return $this->getUrl('quotation/quote/duplicate');
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Get edit message
     *
     * @return \Magento\Framework\Phrase
     */
    protected function getEditMessage()
    {
        return __('Are you sure? This quote will be canceled and a new one will be created instead.');
    }
}
