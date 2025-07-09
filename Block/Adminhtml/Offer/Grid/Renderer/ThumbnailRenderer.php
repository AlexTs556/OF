<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Block\Adminhtml\Offer\Grid\Renderer;

class ThumbnailRenderer extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * ThumbnailRenderer constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Catalog\Helper\Image $imageHelper,
        array $data = []
    ) {
        $this->imageHelper = $imageHelper;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Render thumbnail
     *
     * @param \Magento\Framework\DataObject $productRow
     * @return string
     */
    public function render(\Magento\Framework\DataObject $productRow)
    {
        $imageUrl = $this->imageHelper->init(
            $productRow,
            'product_thumbnail_image'
        )
        ->setImageFile(
            $productRow->getSmallImage()
        )
        ->resize(
            50,
            50
        )
        ->getUrl();

        return '<img src="' . $imageUrl . '"/>';
    }
}
