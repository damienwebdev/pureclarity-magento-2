<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Brand;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Brand\RowData;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Model\Category;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;

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

    /** @var MockObject|StoreManagerInterface */
    private $storeManager;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new RowData(
            $this->storeManager,
            $this->coreConfig
        );
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
            'Description' => $brandId === 1 ? 'A Description' : '',
            'Image' => $brandId === 1 ? '//www.example.com/image-url.jpg' : '',
        ];

        if ($brandId === 1) {
            $brandData['Image'] = '//www.example.com/image-url.jpg';
        } elseif ($brandId === 2) {
            $brandData['Image'] = '//www.example.com/placeholder-image-url.jpg';
        } else {
            $brandData['Image'] = '';
        }

        if ($brandId === 1) {
            $brandData['OverrideImage'] = '//www.example.com/override-image-url.jpg';
        } elseif ($brandId === 2) {
            $brandData['OverrideImage'] = '//www.example.com/secondary-placeholder-image-url.jpg';
        } else {
            $brandData['OverrideImage'] = '';
        }

        $brandData['Link'] = '//www.example.com/brand-' . $brandId;

        if ($brandId > 1) {
            $brandData['ExcludeFromRecommenders'] = true;
        }

        return $brandData;
    }

    /**
     * Sets up a customer MockObject
     * @param int $brandId
     * @param array $data
     * @return MockObject|Category
     */
    public function setupBrand(int $brandId, array $data): MockObject
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
            ->willReturn($data['DisplayName']);

        $brand->expects(self::at(2))
            ->method('getData')
            ->with('description')
            ->willReturn($data['Description']);

        if ($brandId === 1) {
            $brand->method('getImageUrl')
                ->willReturn($data['Image']);
        } else {
            $brand->method('getImageUrl')
                ->willReturn(null);
        }

        if ($brandId === 1) {
            $brand->expects(self::at(4))
                ->method('getData')
                ->with('pureclarity_category_image')
                ->willReturn('override-image-url.jpg');
        } else {
            $brand->expects(self::at(4))
                ->method('getData')
                ->with('pureclarity_category_image')
                ->willReturn(null);
        }

        $brand->method('getUrl')
            ->willReturn($data['Link']);

        if ($brandId === 1) {
            $brand->expects(self::at(6))
                ->method('getData')
                ->with('pureclarity_hide_from_feed')
                ->willReturn('0');
        } else {
            $brand->expects(self::at(6))
                ->method('getData')
                ->with('pureclarity_hide_from_feed')
                ->willReturn('1');
        }

        return $brand;
    }

    /**
     * Sets up a StoreInterface and store manager getStore
     * @param bool $error
     */
    public function setupStore(bool $error = false): void
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
            ->willReturn('1');

        $store->method('getBaseUrl')
            ->willReturn('http://www.example.com/');

        if ($error) {
            $this->storeManager->expects(self::once())
                ->method('getStore')
                ->with(self::STORE_ID)
                ->willThrowException(
                    new NoSuchEntityException(new Phrase('An Error'))
                );
        } else {
            $this->storeManager->expects(self::once())
                ->method('getStore')
                ->with(self::STORE_ID)
                ->willReturn($store);
        }
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
        $this->setupStore();
        $brand = $this->setupBrand(1, $this->mockBrandData(3));
        $this->object->getRowData(self::STORE_ID, $brand);
    }

    /**
     * Tests that a row of data is processed correctly when data is missing some optional fields
     */
    public function testGetRowDataWithoutOptional(): void
    {
        $brand = $this->setupBrand(2, $this->mockBrandData(1));
        $this->object->getRowData(self::STORE_ID, $brand);
    }

    /**
     * Tests that a row of data is processed correctly when data is missing some optional fields
     */
    public function testGetRowDataWithoutOptional2(): void
    {
        $brand = $this->setupBrand(3, $this->mockBrandData(3));
        $this->object->getRowData(self::STORE_ID, $brand);
    }
}
