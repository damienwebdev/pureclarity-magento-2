<?php

declare(strict_types=1);

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Product;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\Type\Product\RowData;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Images;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Categories;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Swatches;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Brand;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Stock;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Attributes;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Children;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Prices;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Catalog\Model\Product;
use Exception;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use ReflectionException;

/**
 * Class PricesTest
 *
 * Tests methods in \Pureclarity\Core\Model\Feed\Type\Product\RowData
 *
 * @see \Pureclarity\Core\Model\Feed\Type\Product\RowData
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class RowDataTest extends TestCase
{
    /** @var LoggerInterface | MockObject */
    private $logger;

    /** @var Images | MockObject */
    private $images;

    /** @var Categories | MockObject */
    private $categories;

    /** @var Swatches | MockObject */
    private $swatches;

    /** @var Brand | MockObject */
    private $brand;

    /** @var Stock | MockObject */
    private $stock;

    /** @var Attributes | MockObject */
    private $attributes;

    /** @var Children | MockObject */
    private $children;

    /** @var Prices | MockObject */
    private $prices;

    /** @var RowData */
    private $rowData;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->prices = $this->createMock(Prices::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->images = $this->createMock(Images::class);
        $this->swatches = $this->createMock(Swatches::class);
        $this->categories = $this->createMock(Categories::class);
        $this->brand = $this->createMock(Brand::class);
        $this->stock = $this->createMock(Stock::class);
        $this->children = $this->createMock(Children::class);
        $this->attributes = $this->createMock(Attributes::class);

        $this->rowData = new RowData(
            $this->logger,
            $this->images,
            $this->categories,
            $this->swatches,
            $this->brand,
            $this->stock,
            $this->attributes,
            $this->children,
            $this->prices
        );
    }

    /**
     * Sets up a product mock
     *
     * @param string $type
     * @param int $visibility
     * @return Product|MockObject
     * @throws ReflectionException
     */
    public function setupProduct(
        string $type = 'simple',
        int $visibility = Product\Visibility::VISIBILITY_BOTH
    ) {
        $product = $this->createPartialMock(
            Product::class,
            [
                'getId',
                'getSku',
                'getName',
                'getData',
                '__call',
                'getTypeId',
                'setStoreId',
                'getUrlModel',
                'getVisibility',
                'getAttributeText'
            ]
        );

        $product->method('getId')
            ->willReturn(1);

        $product->method('getSku')
            ->willReturn('ABC123');

        $product->method('getName')
            ->willReturn('A Product');

        $product->expects(self::at(5))
            ->method('getData')
            ->with('description')
            ->willReturn('A Product Description');

        $product->expects(self::at(6))
            ->method('__call')
            ->with('getShortDescription')
            ->willReturn('A Product Short Description');

        $product->method('getTypeId')
            ->willReturn($type);

        $product->expects(self::once())
            ->method('setStoreId')
            ->willReturn($type);

        $urlModel = $this->createMock(Product\Url::class);

        $urlModel->method('getUrl')
            ->willReturn('http://www.example.com/a-product.html');

        $product->expects(self::once())
            ->method('getUrlModel')
            ->willReturn($urlModel);

        $product->expects(self::once())
            ->method('getVisibility')
            ->willReturn($visibility);

        return $product;
    }

    /**
     * Sets up product purclarity attribute checks
     *
     * @param Product|MockObject $product
     * @param string $overlay
     * @param string $exclude
     * @param string $new
     * @param string $onOffer
     * @return MockObject
     */
    public function setupPureClarityAttributes(
        $product,
        string $overlay = '',
        string $exclude = '',
        string $new = '',
        string $onOffer = ''
    ) {

        $product->expects(self::at(12))
            ->method('getData')
            ->with('pureclarity_search_tags')
            ->willReturn('tag1,tag2');

        $product->expects(self::at(13))
            ->method('getData')
            ->with('pureclarity_overlay_image')
            ->willReturn($overlay);

        $product->expects(self::at(14))
            ->method('getData')
            ->with('pureclarity_exc_rec')
            ->willReturn($exclude);

        $product->expects(self::at(15))
            ->method('getData')
            ->with('pureclarity_newarrival')
            ->willReturn($new);

        $product->expects(self::at(16))
            ->method('getData')
            ->with('pureclarity_onoffer')
            ->willReturn($onOffer);

        return $product;
    }

    /**
     * Sets up a StoreInterface
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
     * Sets up the product image handler
     */
    public function setupProductImages(): void
    {
        $this->images->expects(self::once())
            ->method('getProductImageUrl')
            ->willReturn('//www.example.com/product/image.jpeg');

        $this->images->expects(self::once())
            ->method('getProductGalleryUrls')
            ->willReturn(['//www.example.com/product/image1.jpeg', '//www.example.com/product/image2.jpeg']);
    }

    /**
     * Sets up the categories handler
     */
    public function setupCategories(): void
    {
        $this->categories->expects(self::once())
            ->method('getCategoryData')
            ->willReturn([
                'Categories' => [1,2,3],
                'MagentoCategories' => ['Category 1','Category 2','Category 3']
            ]);
    }

    /**
     * Sets up the swatches handler
     */
    public function setupSwatches(): void
    {
        $this->swatches->expects(self::once())
            ->method('getSwatchData')
            ->willReturn([
                'jsonconfig' => '{"jsonconfig"}',
                'swatchrenderjson' => '{"swatchrenderjson"}'
            ]);
    }

    /**
     * Sets up the brand handler
     * @param int $brandId
     */
    public function setupBrand(int $brandId = 0): void
    {
        $this->brand->expects(self::once())
            ->method('getBrandId')
            ->willReturn($brandId);
    }

    /**
     * Sets up the stock handler
     * @param string $inStock
     * @param bool $excluded
     */
    public function setupStock(string $inStock = 'true', bool $excluded = false): void
    {
        $this->stock->expects(self::once())
            ->method('getStockFlag')
            ->willReturn($inStock);

        $this->stock->expects(self::once())
            ->method('isExcluded')
            ->willReturn($excluded);
    }

    /**
     * Sets up the attributes handler
     * @param Product|MockObject $product
     */
    public function setupAttributes($product): void
    {
        $this->attributes->method('loadAttributes')
            ->willReturn(
                [
                    [
                        'code' => 'attribute_1',
                        'label' => 'Attribute 1',
                        'type' => 'text'
                    ],
                    [
                        'code' => 'attribute_2',
                        'label' => 'Attribute 2',
                        'type' => 'select'
                    ],
                    [
                        'code' => 'attribute_3',
                        'label' => 'Attribute 3',
                        'type' => 'select'
                    ],
                    [
                        'code' => 'MagentoProductType',
                        'label' => 'MagentoProductType',
                        'type' => 'text'
                    ]
                ]
            );

        $product->expects(self::at(17))
            ->method('getData')
            ->with('attribute_1')
            ->willReturn('attribute 1 value');

        $product->expects(self::at(18))
            ->method('getData')
            ->with('attribute_1')
            ->willReturn('attribute 1 value');

        $product->expects(self::at(19))
            ->method('getData')
            ->with('attribute_2')
            ->willReturn(['one','two','three']);

        $product->expects(self::at(20))
            ->method('getAttributeText')
            ->with('attribute_2')
            ->willReturn(['one','two','three']);

        $product->expects(self::at(21))
            ->method('getData')
            ->with('attribute_3')
            ->willReturn('');

        $product->expects(self::at(22))
            ->method('getAttributeText')
            ->willThrowException(new Exception('ignoreme'));

        $product->expects(self::at(23))
            ->method('getData')
            ->with('MagentoProductType')
            ->willReturn('attribute description value');

        $product->expects(self::at(24))
            ->method('getData')
            ->with('MagentoProductType')
            ->willReturn('attribute description value');
    }

    /**
     * Sets up the price handler
     */
    public function setupPrices(): void
    {
        $this->prices->expects(self::once())
            ->method('getPriceData')
            ->willReturn(
                [
                    'Prices' => ['17.00 GBP'],
                    'SalePrices' => [],
                    'GroupPrices' => [
                        1 => [
                            'Prices' => ['18.00 GBP'],
                            'SalePrices' => [],
                        ],
                        2 => [
                            'Prices' => ['19.00 GBP'],
                            'SalePrices' => [],
                        ],
                        3 => [
                            'Prices' => ['20.00 GBP'],
                            'SalePrices' => [],
                        ]
                    ]
                ]
            );
    }

    /**
     * Sets up a child product mock
     *
     * @param int $childId
     * @param bool $attributes
     * @return Product|MockObject
     * @throws ReflectionException
     */
    public function setupChildProduct(int $childId, bool $attributes)
    {
        $product = $this->createPartialMock(
            Product::class,
            [
                'getData',
                '__call',
                'getAttributeText'
            ]
        );

        $product->expects(self::at(0))
            ->method('getData')
            ->with('sku')
            ->willReturn('CHILD' . $childId);

        $product->expects(self::at(1))
            ->method('getData')
            ->with('name')
            ->willReturn('CHILD ' . $childId);

        $product->expects(self::at(2))
            ->method('getData')
            ->with('description')
            ->willReturn('CHILD ' . $childId . ' Description');

        $product->expects(self::at(3))
            ->method('__call')
            ->with('getShortDescription')
            ->willReturn('CHILD ' . $childId . ' Short Description');

        $product->expects(self::at(4))
            ->method('getData')
            ->with('pureclarity_search_tags')
            ->willReturn('ctag1,ctag2,ctag3');

        if ($attributes) {
            $product->expects(self::at(5))
                ->method('getData')
                ->with('attribute_1')
                ->willReturn($childId. ' attribute 1 value');

            $product->expects(self::at(6))
                ->method('getData')
                ->with('attribute_1')
                ->willReturn($childId. ' attribute 1 value');

            $product->expects(self::at(7))
                ->method('getData')
                ->with('attribute_2')
                ->willReturn([$childId. ' one',$childId. ' two',$childId. ' three']);

            $product->expects(self::at(8))
                ->method('getAttributeText')
                ->with('attribute_2')
                ->willReturn([$childId. ' one',$childId. ' two',$childId. ' three']);
        }

        return $product;
    }

    /**
     * Sets up the children handler
     * @param $product
     * @param int $childCount
     * @param bool $attributes
     * @throws ReflectionException
     */
    public function setupChildren($product, int $childCount = 0, bool $attributes = false): void
    {
        $children = [];
        for ($i = 1; $i <= $childCount; $i++) {
            $children[] = $this->setupChildProduct($i, $attributes);
        }

        $this->children->expects(self::once())
            ->method('loadChildData')
            ->with($product)
            ->willReturn($children);
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(RowData::class, $this->rowData);
    }

    /**
     * Tests that an exception is handles correctly.
     *
     * @throws ReflectionException
     */
    public function testGetRowException(): void
    {
        $product = $this->createPartialMock(
            Product::class,
            [
                'getId',
                'getName',
                'getSku',
            ]
        );

        $product->method('getId')
            ->willReturn(1);

        $product->method('getName')
            ->willReturn('A Name');

        $product->method('getSku')
            ->willThrowException(new Exception('An error'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'PureClarity: could not add Product 1 (A Name): An error'
            );

        $store = $this->setupStore();
        $data = $this->rowData->getRowData($store, $product);

        self::assertEquals([], $data);
    }

    /**
     * Tests that a simple product is handled correctly.
     *
     * @throws ReflectionException
     */
    public function testGetRowDataSimple(): void
    {
        $product = $this->setupProduct();
        $product = $this->setupPureClarityAttributes($product);
        $this->setupProductImages();
        $this->setupStock();
        $this->setupBrand(1);
        $this->setupCategories();
        $this->setupPrices();
        $store = $this->setupStore();
        $data = $this->rowData->getRowData($store, $product);

        self::assertEquals([
            'Id' => 1,
            'Sku' => 'ABC123',
            'Title' => 'A Product',
            'Description' => [
                'A Product Description',
                'A Product Short Description'
            ],
            'Link' => '//www.example.com/a-product.html',
            'Image' => '//www.example.com/product/image.jpeg',
            'Categories' => [1,2,3],
            'MagentoCategories' => ['Category 1', 'Category 2', 'Category 3'],
            'MagentoProductType' => 'simple',
            'InStock' => 'true',
            'AllImages' => [
                '//www.example.com/product/image1.jpeg',
                '//www.example.com/product/image2.jpeg'
            ],
            'Brand' => 1,
            'SearchTags' => [
                'tag1',
                'tag2'
            ],
            'Prices' => ['17.00 GBP'],
            'SalePrices' => [],
            'GroupPrices' => [
                1 => [
                    'Prices' => ['18.00 GBP'],
                    'SalePrices' => [],
                ],
                2 => [
                    'Prices' => ['19.00 GBP'],
                    'SalePrices' => [],
                ],
                3 => [
                    'Prices' => ['20.00 GBP'],
                    'SalePrices' => [],
                ]
            ]
        ], $data);
    }

    /**
     * Tests that a simple product that is out of stock is handled correctly when excluded.
     *
     * @throws ReflectionException
     */
    public function testGetRowDataSimpleStockExcluded(): void
    {
        $product = $this->setupProduct();
        $product = $this->setupPureClarityAttributes($product);
        $this->setupProductImages();
        $this->setupStock('false', true);
        $this->setupBrand(1);
        $this->setupCategories();
        $this->setupPrices();
        $store = $this->setupStore();
        $data = $this->rowData->getRowData($store, $product);

        self::assertEquals([
            'Id' => 1,
            'Sku' => 'ABC123',
            'Title' => 'A Product',
            'Description' => [
                'A Product Description',
                'A Product Short Description'
            ],
            'Link' => '//www.example.com/a-product.html',
            'Image' => '//www.example.com/product/image.jpeg',
            'Categories' => [1,2,3],
            'MagentoCategories' => ['Category 1', 'Category 2', 'Category 3'],
            'MagentoProductType' => 'simple',
            'InStock' => 'false',
            'ExcludeFromRecommenders' => true,
            'AllImages' => [
                '//www.example.com/product/image1.jpeg',
                '//www.example.com/product/image2.jpeg'
            ],
            'Brand' => 1,
            'SearchTags' => [
                'tag1',
                'tag2'
            ],
            'Prices' => ['17.00 GBP'],
            'SalePrices' => [],
            'GroupPrices' => [
                1 => [
                    'Prices' => ['18.00 GBP'],
                    'SalePrices' => [],
                ],
                2 => [
                    'Prices' => ['19.00 GBP'],
                    'SalePrices' => [],
                ],
                3 => [
                    'Prices' => ['20.00 GBP'],
                    'SalePrices' => [],
                ]
            ]
        ], $data);
    }

    /**
     * Tests that a simple product is handled correctly when it has data in pureclarity attributes.
     *
     * @throws ReflectionException
     */
    public function testGetRowDataSimpleWithPureClarityAttributes(): void
    {
        $product = $this->setupProduct();
        $product = $this->setupPureClarityAttributes($product, '/image.jpg', '1', '1', '1');
        $this->setupProductImages();
        $this->setupStock();
        $this->setupBrand(1);
        $this->setupCategories();
        $this->setupPrices();
        $store = $this->setupStore();
        $data = $this->rowData->getRowData($store, $product);

        self::assertEquals([
            'Id' => 1,
            'Sku' => 'ABC123',
            'Title' => 'A Product',
            'Description' => [
                'A Product Description',
                'A Product Short Description'
            ],
            'Link' => '//www.example.com/a-product.html',
            'Image' => '//www.example.com/product/image.jpeg',
            'Categories' => [1,2,3],
            'MagentoCategories' => ['Category 1', 'Category 2', 'Category 3'],
            'MagentoProductType' => 'simple',
            'InStock' => 'true',
            'AllImages' => [
                '//www.example.com/product/image1.jpeg',
                '//www.example.com/product/image2.jpeg'
            ],
            'Brand' => 1,
            'ImageOverlay' => '//www.example.com/catalog/product/image.jpg',
            'ExcludeFromRecommenders' => true,
            'NewArrival' => true,
            'OnOffer' => true,
            'SearchTags' => [
                'tag1',
                'tag2'
            ],
            'Prices' => ['17.00 GBP'],
            'SalePrices' => [],
            'GroupPrices' => [
                1 => [
                    'Prices' => ['18.00 GBP'],
                    'SalePrices' => [],
                ],
                2 => [
                    'Prices' => ['19.00 GBP'],
                    'SalePrices' => [],
                ],
                3 => [
                    'Prices' => ['20.00 GBP'],
                    'SalePrices' => [],
                ]
            ]
        ], $data);
    }

    /**
     * Tests that a simple product is handled correctly when it should be excluded from search.
     *
     * @throws ReflectionException
     */
    public function testGetRowDataSimpleExcludedSearch(): void
    {
        $product = $this->setupProduct('simple', Product\Visibility::VISIBILITY_IN_CATALOG);
        $product = $this->setupPureClarityAttributes($product);
        $this->setupProductImages();
        $this->setupStock();
        $this->setupBrand(1);
        $this->setupCategories();
        $this->setupPrices();
        $store = $this->setupStore();
        $data = $this->rowData->getRowData($store, $product);

        self::assertEquals([
            'Id' => 1,
            'Sku' => 'ABC123',
            'Title' => 'A Product',
            'Description' => [
                'A Product Description',
                'A Product Short Description'
            ],
            'Link' => '//www.example.com/a-product.html',
            'Image' => '//www.example.com/product/image.jpeg',
            'Categories' => [1,2,3],
            'MagentoCategories' => ['Category 1', 'Category 2', 'Category 3'],
            'MagentoProductType' => 'simple',
            'InStock' => 'true',
            'AllImages' => [
                '//www.example.com/product/image1.jpeg',
                '//www.example.com/product/image2.jpeg'
            ],
            'ExcludeFromSearch' => true,
            'Brand' => 1,
            'SearchTags' => [
                'tag1',
                'tag2'
            ],
            'Prices' => ['17.00 GBP'],
            'SalePrices' => [],
            'GroupPrices' => [
                1 => [
                    'Prices' => ['18.00 GBP'],
                    'SalePrices' => [],
                ],
                2 => [
                    'Prices' => ['19.00 GBP'],
                    'SalePrices' => [],
                ],
                3 => [
                    'Prices' => ['20.00 GBP'],
                    'SalePrices' => [],
                ]
            ]
        ], $data);
    }

    /**
     * Tests that a simple product is handled correctly when is should have the exclude from listing flag set.
     *
     * @throws ReflectionException
     */
    public function testGetRowDataSimpleExcludedListing(): void
    {
        $product = $this->setupProduct('simple', Product\Visibility::VISIBILITY_IN_SEARCH);
        $product = $this->setupPureClarityAttributes($product);
        $this->setupProductImages();
        $this->setupStock();
        $this->setupBrand(1);
        $this->setupCategories();
        $this->setupPrices();
        $store = $this->setupStore();
        $data = $this->rowData->getRowData($store, $product);

        self::assertEquals([
            'Id' => 1,
            'Sku' => 'ABC123',
            'Title' => 'A Product',
            'Description' => [
                'A Product Description',
                'A Product Short Description'
            ],
            'Link' => '//www.example.com/a-product.html',
            'Image' => '//www.example.com/product/image.jpeg',
            'Categories' => [1,2,3],
            'MagentoCategories' => ['Category 1', 'Category 2', 'Category 3'],
            'MagentoProductType' => 'simple',
            'InStock' => 'true',
            'AllImages' => [
                '//www.example.com/product/image1.jpeg',
                '//www.example.com/product/image2.jpeg'
            ],
            'ExcludeFromProductListing' => true,
            'Brand' => 1,
            'SearchTags' => [
                'tag1',
                'tag2'
            ],
            'Prices' => ['17.00 GBP'],
            'SalePrices' => [],
            'GroupPrices' => [
                1 => [
                    'Prices' => ['18.00 GBP'],
                    'SalePrices' => [],
                ],
                2 => [
                    'Prices' => ['19.00 GBP'],
                    'SalePrices' => [],
                ],
                3 => [
                    'Prices' => ['20.00 GBP'],
                    'SalePrices' => [],
                ]
            ]
        ], $data);
    }

    /**
     * Tests that a simple product is handled correctly when there is attribute data added.
     *
     * @throws ReflectionException
     */
    public function testGetRowDataSimpleWithAttributes(): void
    {
        $product = $this->setupProduct();
        $product = $this->setupPureClarityAttributes($product);
        $this->setupProductImages();
        $this->setupStock();
        $this->setupBrand(1);
        $this->setupCategories();
        $this->setupPrices();
        $this->setupAttributes($product);
        $store = $this->setupStore();
        $data = $this->rowData->getRowData($store, $product);

        self::assertEquals([
            'Id' => 1,
            'Sku' => 'ABC123',
            'Title' => 'A Product',
            'Description' => [
                'A Product Description',
                'A Product Short Description'
            ],
            'Link' => '//www.example.com/a-product.html',
            'Image' => '//www.example.com/product/image.jpeg',
            'Categories' => [1,2,3],
            'MagentoCategories' => ['Category 1', 'Category 2', 'Category 3'],
            'MagentoProductType' => ['simple', 'attribute description value'],
            'InStock' => 'true',
            'AllImages' => [
                '//www.example.com/product/image1.jpeg',
                '//www.example.com/product/image2.jpeg'
            ],
            'Brand' => 1,
            'SearchTags' => [
                'tag1',
                'tag2'
            ],
            'Attribute 1' => ['attribute 1 value'],
            'Attribute 2' => ['one','two','three'],
            'Prices' => ['17.00 GBP'],
            'SalePrices' => [],
            'GroupPrices' => [
                1 => [
                    'Prices' => ['18.00 GBP'],
                    'SalePrices' => [],
                ],
                2 => [
                    'Prices' => ['19.00 GBP'],
                    'SalePrices' => [],
                ],
                3 => [
                    'Prices' => ['20.00 GBP'],
                    'SalePrices' => [],
                ]
            ]
        ], $data);
    }

    /**
     * Tests that a configurable product is handled correctly.
     * @throws ReflectionException
     */
    public function testGetRowDataConfigurable(): void
    {
        $product = $this->setupProduct(Configurable::TYPE_CODE);
        $product = $this->setupPureClarityAttributes($product);
        $this->setupProductImages();
        $this->setupStock();
        $this->setupBrand();
        $this->setupCategories();
        $this->setupPrices();
        $this->setupSwatches();
        $this->setupChildren($product, 2);
        $store = $this->setupStore();
        $data = $this->rowData->getRowData($store, $product);

        self::assertEquals([
            'Id' => 1,
            'Sku' => 'ABC123',
            'Title' => 'A Product',
            'Description' => [
                'A Product Description',
                'A Product Short Description',
                'CHILD 1 Description',
                'CHILD 1 Short Description',
                'CHILD 2 Description',
                'CHILD 2 Short Description'
            ],
            'Link' => '//www.example.com/a-product.html',
            'Image' => '//www.example.com/product/image.jpeg',
            'Categories' => [1,2,3],
            'MagentoCategories' => ['Category 1', 'Category 2', 'Category 3'],
            'MagentoProductType' => 'configurable',
            'InStock' => 'true',
            'AllImages' => [
                '//www.example.com/product/image1.jpeg',
                '//www.example.com/product/image2.jpeg'
            ],
            'jsonconfig' => '{"jsonconfig"}',
            'swatchrenderjson' => '{"swatchrenderjson"}',
            'SearchTags' => [
                'tag1',
                'tag2',
                'ctag1',
                'ctag2',
                'ctag3',
            ],
            'AssociatedSkus' => [
                'CHILD1',
                'CHILD2'
            ],
            'AssociatedTitles' => [
                'CHILD 1',
                'CHILD 2'
            ],
            'Prices' => ['17.00 GBP'],
            'SalePrices' => [],
            'GroupPrices' => [
                1 => [
                    'Prices' => ['18.00 GBP'],
                    'SalePrices' => [],
                ],
                2 => [
                    'Prices' => ['19.00 GBP'],
                    'SalePrices' => [],
                ],
                3 => [
                    'Prices' => ['20.00 GBP'],
                    'SalePrices' => [],
                ]
            ]
        ], $data);
    }

    /**
     * Tests that a configurable product is handled correctly with attribute data.
     * @throws ReflectionException
     */
    public function testGetRowDataConfigurableWithAttributes(): void
    {
        $product = $this->setupProduct(Configurable::TYPE_CODE);
        $product = $this->setupPureClarityAttributes($product);
        $this->setupProductImages();
        $this->setupStock();
        $this->setupBrand();
        $this->setupCategories();
        $this->setupPrices();
        $this->setupSwatches();
        $this->setupAttributes($product);
        $this->setupChildren($product, 2, true);
        $store = $this->setupStore();
        $data = $this->rowData->getRowData($store, $product);

        self::assertEquals([
            'Id' => 1,
            'Sku' => 'ABC123',
            'Title' => 'A Product',
            'Description' => [
                'A Product Description',
                'A Product Short Description',
                'CHILD 1 Description',
                'CHILD 1 Short Description',
                'CHILD 2 Description',
                'CHILD 2 Short Description'
            ],
            'Link' => '//www.example.com/a-product.html',
            'Image' => '//www.example.com/product/image.jpeg',
            'Categories' => [1,2,3],
            'MagentoCategories' => ['Category 1', 'Category 2', 'Category 3'],
            'MagentoProductType' => ['configurable', 'attribute description value'],
            'InStock' => 'true',
            'AllImages' => [
                '//www.example.com/product/image1.jpeg',
                '//www.example.com/product/image2.jpeg'
            ],
            'jsonconfig' => '{"jsonconfig"}',
            'swatchrenderjson' => '{"swatchrenderjson"}',
            'SearchTags' => [
                'tag1',
                'tag2',
                'ctag1',
                'ctag2',
                'ctag3',
            ],
            'Attribute 1' => ['attribute 1 value', '1 attribute 1 value', '2 attribute 1 value'],
            'Attribute 2' => ['one','two','three','1 one','1 two','1 three','2 one','2 two','2 three'],
            'AssociatedSkus' => [
                'CHILD1',
                'CHILD2'
            ],
            'AssociatedTitles' => [
                'CHILD 1',
                'CHILD 2'
            ],
            'Prices' => ['17.00 GBP'],
            'SalePrices' => [],
            'GroupPrices' => [
                1 => [
                    'Prices' => ['18.00 GBP'],
                    'SalePrices' => [],
                ],
                2 => [
                    'Prices' => ['19.00 GBP'],
                    'SalePrices' => [],
                ],
                3 => [
                    'Prices' => ['20.00 GBP'],
                    'SalePrices' => [],
                ]
            ]
        ], $data);
    }
}
