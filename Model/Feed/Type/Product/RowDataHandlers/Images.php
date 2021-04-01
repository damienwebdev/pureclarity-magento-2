<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Catalog\Model\Product;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class Images
 *
 * Loads image data for the given product
 */
class Images
{
    /** @var Gallery $galleryBlock */
    private $galleryBlock;

    /** @var BlockFactory $blockFactory */
    private $blockFactory;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var ReadHandler $galleryReadHandler */
    private $galleryReadHandler;

    /**
     * @param BlockFactory $blockFactory
     * @param CoreConfig $coreConfig
     * @param ReadHandler $galleryReadHandler
     */
    public function __construct(
        BlockFactory $blockFactory,
        CoreConfig $coreConfig,
        ReadHandler $galleryReadHandler
    ) {
        $this->blockFactory       = $blockFactory;
        $this->coreConfig         = $coreConfig;
        $this->galleryReadHandler = $galleryReadHandler;
    }

    /**
     * Gets the main image for a product
     *
     * @param Product|ProductInterface $product
     * @param StoreInterface $store
     *
     * @return string
     */
    public function getProductImageUrl($product, StoreInterface $store): string
    {
        $galleryBlock = $this->getGalleryBlock();
        $galleryBlock->setData('product', $product);
            
        if ($product->getImage() && $product->getImage() !== 'no_selection') {
            $productImage = $galleryBlock->getImage($product, 'category_page_grid');
            $productImageUrl = $productImage->getImageUrl();
        } else {
            $productImageUrl = $this->coreConfig->getProductPlaceholderUrl((int)$store->getId());
            if ($productImageUrl === null) {
                $productImageUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
                                 . 'catalog/product/placeholder/'
                                 . $store->getConfig('catalog/placeholder/image_placeholder');
            }
        }

        return $productImageUrl;
    }

    /**
     * Gets the gallery images for a product
     *
     * @param Product|ProductInterface $product
     *
     * @return string[]
     */
    public function getProductGalleryUrls($product): array
    {
        $galleryBlock = $this->getGalleryBlock();
        $galleryBlock->setData('product', $product);

        $this->galleryReadHandler->execute($product);
        $productImages = $galleryBlock->getGalleryImages()->getItems();

        $allImages = [];

        foreach ($productImages as $image) {
            $allImages[] = str_replace(
                ["https:", "http:"],
                '',
                (is_object($image) ? $image->getData('small_image_url') : $image)
            );
        }

        return $allImages;
    }
    
    /**
     * Gets a Gallery Block for use with gallery image url generation
     *
     * @return Gallery
     */
    private function getGalleryBlock(): Gallery
    {
        if ($this->galleryBlock === null) {
            $config = [
                'small_image' => [
                    'image_id' => 'product_page_image_small',
                    'data_object_key' => 'small_image_url',
                    'json_object_key' => 'thumb'
                ],
                'medium_image' => [
                    'image_id' => 'product_page_image_medium',
                    'data_object_key' => 'medium_image_url',
                    'json_object_key' => 'img'
                ],
                'large_image' => [
                    'image_id' => 'product_page_image_large',
                    'data_object_key' => 'large_image_url',
                    'json_object_key' => 'full'
                ]
            ];

            $this->galleryBlock = $this->blockFactory->createBlock(
                Gallery::class,
                [
                    'galleryImagesConfig' => $config
                ]
            );
        }

        return $this->galleryBlock;
    }
}
