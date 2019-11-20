<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\ProductExport;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Catalog\Model\Product;
use Pureclarity\Core\Model\ProductExport\Images;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Block\Product\Image;
use Magento\Framework\Data\Collection;

/**
 * Class ImagesTest
 *
 * Tests the methods in \Pureclarity\Core\Model\ProductExport\Images
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
    private $galleryImageCollection;

    protected function setUp()
    {
        $this->galleryBlock = $this->getMockBuilder(Gallery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockFactory = $this->getMockBuilder(BlockFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->galleryReadHandler = $this->getMockBuilder(ReadHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->galleryImage = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->setMethods(
                ['getImageUrl', 'getData']
            )
            ->getMock();

        $this->galleryImageCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Images(
            $this->blockFactory,
            $this->coreConfig,
            $this->galleryReadHandler
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Images::class, $this->object);
    }

    public function testGetProductImageUrlWithImage()
    {
        $this->setupGalleryBlock();

        $this->galleryBlock->expects($this->at(0))
            ->method('setData')
            ->with('product', $this->product);

        $this->product->expects($this->any())
            ->method('getImage')
            ->willReturn('image.jpg');

        $this->galleryBlock->expects($this->at(1))
            ->method('getImage')
            ->with($this->product, 'category_page_grid')
            ->willReturn($this->galleryImage);

        $this->galleryImage->expects($this->at(0))
            ->method('getImageUrl')
            ->willReturn('/cached/image.jpg');

        $url = $this->object->getProductImageUrl($this->product, $this->store);

        $this->assertEquals('/cached/image.jpg', $url);
    }

    public function testGetProductImageUrlNoImageWithPlaceholder()
    {
        $this->setupGalleryBlock();

        $this->galleryBlock->expects($this->at(0))
            ->method('setData')
            ->with('product', $this->product);

        $this->product->expects($this->any())
            ->method('getImage')
            ->willReturn('no_selection');

        $this->store->expects($this->at(0))
            ->method('getId')
            ->willReturn(1);

        $this->coreConfig->expects($this->at(0))
            ->method('getProductPlaceholderUrl')
            ->with(1)
            ->willReturn('placeholder.jpg');

        $url = $this->object->getProductImageUrl($this->product, $this->store);
        $this->assertEquals('placeholder.jpg', $url);
    }

    public function testGetProductImageUrlNoImageNoPlaceholder()
    {
        $this->setupGalleryBlock();

        $this->galleryBlock->expects($this->at(0))
            ->method('setData')
            ->with('product', $this->product);

        $this->product->expects($this->any())
            ->method('getImage')
            ->willReturn('no_selection');

        $this->store->expects($this->at(0))
            ->method('getId')
            ->willReturn(1);

        $this->coreConfig->expects($this->at(0))
            ->method('getProductPlaceholderUrl')
            ->with(1)
            ->willReturn(null);

        $this->store->expects($this->at(1))
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn('https://www.pureclarity.com/');

        $this->store->expects($this->at(2))
            ->method('getConfig')
            ->with('catalog/placeholder/image_placeholder')
            ->willReturn('magento-placeholder.jpg');

        $url = $this->object->getProductImageUrl($this->product, $this->store);
        $this->assertEquals('https://www.pureclarity.com/catalog/product/placeholder/magento-placeholder.jpg', $url);
    }



    public function testGetProductGalleryUrls()
    {
        $this->setupGalleryBlock();

        $this->galleryBlock->expects($this->at(0))
            ->method('setData')
            ->with('product', $this->product);

        $this->galleryReadHandler->expects($this->at(0))
            ->method('execute')
            ->with($this->product);

        $this->galleryBlock->expects($this->at(1))
            ->method('getGalleryImages')
            ->willReturn($this->galleryImageCollection);

        $this->galleryImageCollection->expects($this->at(0))
            ->method('getItems')
            ->willReturn([$this->galleryImage, $this->galleryImage]);

        $this->galleryImage->expects($this->at(0))
            ->method('getData')
            ->with('small_image_url')
            ->willReturn('smallImage.jpg');

        $this->galleryImage->expects($this->at(1))
            ->method('getData')
            ->with('small_image_url')
            ->willReturn('smallImage2.jpg');

        $urls = $this->object->getProductGalleryUrls($this->product);
        $this->assertEquals(['smallImage.jpg', 'smallImage2.jpg'], $urls);
    }

    private function setupGalleryBlock() {
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

        $this->blockFactory->expects($this->at(0))
            ->method('createBlock')
            ->with(
                Gallery::class,
                [
                    'galleryImagesConfig' => $config
                ]
            )
            ->willReturn($this->galleryBlock);
    }
}
