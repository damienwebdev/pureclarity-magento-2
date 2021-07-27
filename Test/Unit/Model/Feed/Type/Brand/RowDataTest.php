<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Brand;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\Type\Brand\RowData;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Model\Category;
use Magento\Store\Api\Data\StoreInterface;
use ReflectionException;

/**
 * Class RowDataTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Brand\RowData
 */
class RowDataTest extends TestCase
{
    /** @var int */
    private const STORE_ID = 1;

    /** @var RowData */
    private $object;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var LoggerInterface | MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->object = new RowData(
            $this->coreConfig,
            $this->logger
        );
    }

    /**
     * Sets up config value loading
     * @param bool $configured
     */
    public function setupConfig(bool $configured = false): void
    {
        if ($configured) {
            $this->coreConfig->expects(self::once())
                ->method('getCategoryPlaceholderUrl')
                ->with(self::STORE_ID)
                ->willReturn('http://www.example.com/placeholder-image-url.jpg');

            $this->coreConfig->expects(self::once())
                ->method('getSecondaryCategoryPlaceholderUrl')
                ->with(self::STORE_ID)
                ->willReturn('https://www.example.com/secondary-placeholder-image-url.jpg');
        } else {
            $this->coreConfig->expects(self::once())
                ->method('getCategoryPlaceholderUrl')
                ->with(self::STORE_ID)
                ->willReturn('');

            $this->coreConfig->expects(self::once())
                ->method('getSecondaryCategoryPlaceholderUrl')
                ->with(self::STORE_ID)
                ->willReturn('');
        }
    }

    /**
     * Builds dummy data for brand feed
     * @param int $brandId
     * @return array
     */
    public function mockBrandData(int $brandId): array
    {
        $brandData = [
            'Id' => $brandId,
            'DisplayName' =>  'Brand ' . $brandId,
            'Description' => '',
            'Image' => '',
        ];

        // Brand 1 only hase bare minimum data set
        if ($brandId === 1) {
            $brandData['Link'] = '//www.example.com/brand-' . $brandId;
        }

        // Brand 2 has all optional fields set
        if ($brandId === 2) {
            $brandData['Description'] = 'A Description of 2';
            $brandData['Image'] = '//www.example.com/image-url.jpg';
            $brandData['OverrideImage'] = '//www.example.com/catalog/pureclarity_category_image/override-image-url.jpg';
            $brandData['Link'] = '//www.example.com/brand-' . $brandId;
            $brandData['ExcludeFromRecommenders'] = true;
        }

        // Brand 2 has bare minimum, but placeholders are configured
        if ($brandId === 3) {
            $brandData['Image'] = '//www.example.com/placeholder-image-url.jpg';
            $brandData['Link'] = '//www.example.com/brand-' . $brandId;
            $brandData['OverrideImage'] = '//www.example.com/secondary-placeholder-image-url.jpg';
        }

        return $brandData;
    }

    /**
     * Sets up a brand MockObject
     * @param int $brandId
     * @return MockObject|Category
     */
    public function setupBaseBrand(int $brandId)
    {
        $brand = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId',
                'getName',
                'getData',
                'getImageUrl',
                'getUrl'
            ])
            ->getMock();

        $brand->method('getId')
            ->willReturn($brandId);

        $brand->method('getName')
            ->willReturn('Brand ' . $brandId);

        $brand->method('getUrl')
            ->willReturn('http://www.example.com/brand-' . $brandId);

        return $brand;
    }

    /**
     * Sets up a brand MockObject with the following requirements:
     *
     * default, only has bare minimum data
     *
     * @return MockObject|Category
     */
    public function setupBrand1()
    {
        $brandId = 1;
        $brand = $this->setupBaseBrand($brandId);

        $brand->expects(self::at(4))
            ->method('getData')
            ->with('description')
            ->willReturn('');

        $brand->method('getImageUrl')
            ->willReturn('');

        $brand->expects(self::at(6))
            ->method('getData')
            ->with('pureclarity_category_image')
            ->willReturn(null);

        $brand->expects(self::at(8))
            ->method('getData')
            ->with('pureclarity_hide_from_feed')
            ->willReturn('0');

        return $brand;
    }

    /**
     * Sets up a brand MockObject with the following requirements:
     *
     * has an image set
     * has a description
     * is excluded from recommenders
     * has an override image set
     *
     * @return MockObject|Category
     */
    public function setupBrand2()
    {
        $brandId = 2;
        $brand = $this->setupBaseBrand($brandId);

        $brand->expects(self::at(4))
            ->method('getData')
            ->with('description')
            ->willReturn('A Description of 2');

        $brand->method('getImageUrl')
            ->willReturn('http://www.example.com/image-url.jpg');

        $brand->expects(self::at(6))
            ->method('getData')
            ->with('pureclarity_category_image')
            ->willReturn('override-image-url.jpg');

        $brand->expects(self::at(8))
            ->method('getData')
            ->with('pureclarity_hide_from_feed')
            ->willReturn('1');

        return $brand;
    }

    /**
     * Sets up a brand MockObject with the following requirements:
     *
     * Same as default, but with config will use placeholder images
     *
     * @return MockObject|Category
     */
    public function setupBrand3()
    {
        $brandId = 3;
        $brand = $this->setupBaseBrand($brandId);

        $brand->expects(self::at(4))
            ->method('getData')
            ->with('description')
            ->willReturn('');

        $brand->method('getImageUrl')
            ->willReturn(null);

        $brand->expects(self::at(6))
            ->method('getData')
            ->with('pureclarity_category_image')
            ->willReturn(null);

        $brand->expects(self::at(8))
            ->method('getData')
            ->with('pureclarity_hide_from_feed')
            ->willReturn('0');

        return $brand;
    }

    /**
     * Sets up a StoreInterface and store manager getStore
     *
     * @return StoreInterface|MockObject
     * @throws ReflectionException
     */
    public function setupStore()
    {
        $store = $this->getMockForAbstractClass(
            StoreInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getId', 'getBaseUrl']
        );

        $store->method('getId')
            ->willReturn(1);

        $store->method('getBaseUrl')
            ->willReturn('http://www.example.com/');

        return $store;
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(RowData::class, $this->object);
    }

    /**
     * Tests that a row of data is processed correctly
     */
    public function testGetRowData(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $data = $this->mockBrandData(1);
        $brand = $this->setupBrand1();
        $rowData = $this->object->getRowData($store, $brand);
        self::assertEquals($data, $rowData);
    }

    /**
     * Tests that a row of data is processed correctly when data has all optional fields
     */
    public function testGetRowDataWithOptional(): void
    {
        $store = $this->setupStore();
        $data = $this->mockBrandData(2);
        $brand = $this->setupBrand2();
        $rowData = $this->object->getRowData($store, $brand);
        self::assertEquals($data, $rowData);
    }

    /**
     * Tests that a row of data is processed correctly when data is missing some optional fields
     *
     */
    public function testGetRowDataWithPlaceholder(): void
    {
        $store = $this->setupStore();
        $this->setupConfig(true);
        $data = $this->mockBrandData(3);
        $brand = $this->setupBrand3();
        $rowData = $this->object->getRowData($store, $brand);
        self::assertEquals($data, $rowData);
    }
}
