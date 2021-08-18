<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Product\RowDataHandlers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Catalog\Model\Product;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Images;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Block\Product\Image;
use Magento\Framework\Data\Collection;
use ReflectionException;

/**
 * Class ImagesTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Images
 * @see \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Images
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImagesTest extends TestCase
{
    /** @var Images $object */
    private $object;

    /** @var MockObject|Gallery $galleryBlock */
    private $galleryBlock;

    /** @var MockObject|BlockFactory $blockFactory */
    private $blockFactory;

    /** @var MockObject|CoreConfig $coreConfig */
    private $coreConfig;

    /** @var MockObject|ReadHandler $galleryReadHandler */
    private $galleryReadHandler;

    /** @var MockObject|Product $product */
    private $product;

    /** @var MockObject|Store $store */
    private $store;

    /** @var MockObject|Image */
    private $galleryImage;

    /** @var MockObject|Collection */
    private $galleryCollection;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->galleryBlock = $this->createMock(Gallery::class);
        $this->blockFactory = $this->createMock(BlockFactory::class);
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->galleryReadHandler = $this->createMock(ReadHandler::class);
        $this->product = $this->createMock(Product::class);
        $this->store = $this->createMock(Store::class);
        $this->galleryImage = $this->createPartialMock(
            Image::class,
            ['__call', 'getData']
        );
        $this->galleryCollection = $this->createMock(Collection::class);

        $this->object = new Images(
            $this->blockFactory,
            $this->coreConfig,
            $this->galleryReadHandler
        );
    }

    /**
     * Sets up the Gallery block mock
     */
    private function setupGalleryBlock(): void
    {
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

        $this->blockFactory->expects(self::at(0))
            ->method('createBlock')
            ->with(
                Gallery::class,
                [
                    'galleryImagesConfig' => $config
                ]
            )
            ->willReturn($this->galleryBlock);
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Images::class, $this->object);
    }

    /**
     * Test that getProductImageUrl returns the configured image URL for a product
     */
    public function testGetProductImageUrlWithImage(): void
    {
        $this->setupGalleryBlock();

        $this->galleryBlock->expects(self::at(0))
            ->method('setData')
            ->with('product', $this->product);

        $this->product->method('getImage')
            ->willReturn('image.jpg');

        $this->galleryBlock->expects(self::at(1))
            ->method('getImage')
            ->with($this->product, 'category_page_grid')
            ->willReturn($this->galleryImage);

        $this->galleryImage->expects(self::at(0))
            ->method('__call')
            ->with('getImageUrl')
            ->willReturn('/cached/image.jpg');

        $url = $this->object->getProductImageUrl($this->product, $this->store);

        self::assertEquals('/cached/image.jpg', $url);
    }

    /**
     * Test that getProductImageUrl returns the pureclarity placeholder URL for a product when no product image set
     */
    public function testGetProductImageUrlNoImageWithPlaceholder(): void
    {
        $this->setupGalleryBlock();

        $this->galleryBlock->expects(self::at(0))
            ->method('setData')
            ->with('product', $this->product);

        $this->product->method('getImage')
            ->willReturn('no_selection');

        $this->store->expects(self::at(0))
            ->method('getId')
            ->willReturn(1);

        $this->coreConfig->expects(self::at(0))
            ->method('getProductPlaceholderUrl')
            ->with(1)
            ->willReturn('placeholder.jpg');

        $url = $this->object->getProductImageUrl($this->product, $this->store);
        self::assertEquals('placeholder.jpg', $url);
    }

    /**
     * Test that getProductImageUrl returns the magento placeholder URL when no product image / pureclarity placeholder
     */
    public function testGetProductImageUrlNoImageNoPlaceholder(): void
    {
        $this->setupGalleryBlock();

        $this->galleryBlock->expects(self::at(0))
            ->method('setData')
            ->with('product', $this->product);

        $this->product->method('getImage')
            ->willReturn('no_selection');

        $this->store->expects(self::at(0))
            ->method('getId')
            ->willReturn(1);

        $this->coreConfig->expects(self::at(0))
            ->method('getProductPlaceholderUrl')
            ->with(1)
            ->willReturn(null);

        $this->store->expects(self::at(1))
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn('https://www.pureclarity.com/');

        $this->store->expects(self::at(2))
            ->method('getConfig')
            ->with('catalog/placeholder/image_placeholder')
            ->willReturn('magento-placeholder.jpg');

        $url = $this->object->getProductImageUrl($this->product, $this->store);
        self::assertEquals('https://www.pureclarity.com/catalog/product/placeholder/magento-placeholder.jpg', $url);
    }

    /**
     * Test that getProductGalleryUrls returns the gallery urls for a product
     */
    public function testGetProductGalleryUrls(): void
    {
        $this->setupGalleryBlock();

        $this->galleryBlock->expects(self::at(0))
            ->method('setData')
            ->with('product', $this->product);

        $this->galleryReadHandler->expects(self::at(0))
            ->method('execute')
            ->with($this->product);

        $this->galleryBlock->expects(self::at(1))
            ->method('getGalleryImages')
            ->willReturn($this->galleryCollection);

        $this->galleryCollection->expects(self::at(0))
            ->method('getItems')
            ->willReturn([$this->galleryImage, $this->galleryImage]);

        $this->galleryImage->expects(self::at(0))
            ->method('getData')
            ->with('small_image_url')
            ->willReturn('smallImage.jpg');

        $this->galleryImage->expects(self::at(1))
            ->method('getData')
            ->with('small_image_url')
            ->willReturn('smallImage2.jpg');

        $urls = $this->object->getProductGalleryUrls($this->product);
        self::assertEquals(['smallImage.jpg', 'smallImage2.jpg'], $urls);
    }
}
