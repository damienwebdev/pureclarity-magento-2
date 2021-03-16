<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Delta\Type;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Store\Model\App\Emulation;
use phpDocumentor\Reflection\DocBlock\Serializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PureClarity\Api\Delta\Type\ProductFactory;
use PureClarity\Api\Delta\Type\Product as DeltaHandler;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Delta\Type\Product;
use Pureclarity\Core\Model\ProductExport;
use Pureclarity\Core\Model\ProductExportFactory;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Class ProductTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Delta\Type\Product
 */
class ProductTest extends TestCase
{
    /** @var mixed[] $dummyProductData */
    private const PRODUCT_DATA = [
        [
            'id' => 1,
            'status' => Status::STATUS_DISABLED,
            'visibility' => Visibility::VISIBILITY_NOT_VISIBLE
        ]
    ];

    /** @var Product $object */
    private $object;

    /** @var Emulation|MockObject */
    private $appEmulation;

    /** @var ProductCollectionFactory|MockObject */
    private $productCollectionFactory;

    /** @var Collection|MockObject */
    private $productCollection;

    /** @var MockObject|ProductFactory */
    private $deltaFactory;

    /** @var MockObject|DeltaHandler */
    private $deltaHandler;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|ProductExportFactory */
    private $productExportFactory;

    /** @var MockObject|ProductExport */
    private $productExport;

    /** @var MockObject|LoggerInterface */
    private $logger;

    protected function setUp() : void
    {
        $this->appEmulation = $this->getMockBuilder(Emulation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productCollectionFactory = $this->getMockBuilder(ProductCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'addAttributeToFilter',
                'setStoreId',
                'count',
                'getItems',
                'addStoreFilter',
                'addUrlRewrite',
                'addAttributeToSelect',
                'addMinimalPrice',
                'addTaxPercents'
            ])
            ->getMock();

        $this->productCollectionFactory->method('create')->willReturn($this->productCollection);

        $this->deltaFactory = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deltaHandler = $this->getMockBuilder(DeltaHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deltaFactory->method('create')->willReturn($this->deltaHandler);

        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productExportFactory = $this->getMockBuilder(ProductExportFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productExport = $this->getMockBuilder(ProductExport::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productExportFactory->method('create')->willReturn($this->productExport);

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Product(
            $this->appEmulation,
            $this->productCollectionFactory,
            $this->deltaFactory,
            $this->coreConfig,
            $this->productExportFactory,
            $this->logger
        );
    }

    /**
     * Generates a mock product model.
     *
     * @param int $id
     * @param array $prodInfo
     * @return MockObject|ProductModel
     */
    private function getProductModel(int $id, array $prodInfo)
    {
        $model = $this->getMockBuilder(ProductModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getVisibility', 'getData'])
            ->getMock();

        $model->method('getId')
            ->willReturn($id);

        $model->method('getVisibility')
            ->willReturn($prodInfo['visibility']);

        $model->method('getData')
            ->with('status')
            ->willReturn($prodInfo['status']);

        return $model;
    }

    /**
     * Build product data expectations.
     *
     * @param array $productData
     * @param bool $returnEmpty
     */
    public function setUpProductData(array $productData, $returnEmpty = false): void
    {
        $products = [];
        if ($returnEmpty === false) {
            $x = 0;
            foreach ($productData as $id => $product) {
                $model = $this->getProductModel($id, $product);
                $products[] = $model;
                if (isset($product['export'])) {
                    if ($product['export'] === true) {
                        $this->productExport->expects(self::at($x+1))
                            ->method('processProduct')
                            ->with($model, $x)
                            ->willReturn(['somedata']);
                    } else {
                        $this->productExport->expects(self::at($x+1))
                            ->method('processProduct')
                            ->with($model, $x)
                            ->willReturn(null);
                    }
                    $x++;
                }
            }
        }

        $this->productCollection->expects(self::at(0))->method('setStoreId')->with(1);
        $this->productCollection->expects(self::at(4))
            ->method('addAttributeToFilter')->with('entity_id', array_keys($productData));
        $this->productCollection->expects(self::at(7))->method('count')->willReturn(count($products));
        if ($returnEmpty === false) {
            $this->productCollection->expects(self::at(8))->method('getItems')->willReturn($products);
        }
    }

    /**
     * Test class sets up correctly.
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Product::class, $this->object);
    }

    /**
     * Tests runDelta with no products loaded
     */
    public function testRunDeltaNoData(): void
    {
        $products = [
            1 => [
                'status' => Status::STATUS_DISABLED,
                'visibility' => Visibility::VISIBILITY_NOT_VISIBLE
            ]
        ];

        $this->setUpProductData($products, true);
        $this->deltaHandler->expects(self::never())->method('addDelete');
        $this->deltaHandler->expects(self::never())->method('addData');
        $this->deltaHandler->expects(self::never())->method('send');
        $this->object->runDelta(1, array_keys($products));
    }

    /**
     * Tests runDelta with no visible products available.
     */
    public function testRunDeltaNoVisibleProducts(): void
    {
        $products = [
            1 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_NOT_VISIBLE
            ]
        ];
        $this->setUpProductData($products);
        $this->deltaHandler->expects(self::once())->method('addDelete');
        $this->deltaHandler->expects(self::never())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');
        $this->object->runDelta(1, array_keys($products));
    }

    /**
     * Tests runDelta with no enabled products available.
     */
    public function testRunDeltaNoEnabledProducts(): void
    {
        $products = [
            1 => [
                'status' => Status::STATUS_DISABLED,
                'visibility' => Visibility::VISIBILITY_BOTH
            ]
        ];
        $this->setUpProductData($products);
        $this->deltaHandler->expects(self::once())->method('addDelete');
        $this->deltaHandler->expects(self::never())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');
        $this->object->runDelta(1, array_keys($products));
    }

    /**
     * Tests runDelta with a product that does not get returned from the product export class.
     */
    public function testRunDeltaOneExcludedProduct(): void
    {
        $products = [
            1 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'export' => false
            ]
        ];
        $this->setUpProductData($products);

        $this->deltaHandler->expects(self::once())->method('addDelete');
        $this->deltaHandler->expects(self::never())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');
        $this->object->runDelta(1, array_keys($products));
    }

    /**
     * Tests runDelta with a product that gets returned from the product export class correctly.
     */
    public function testRunDeltaOneProduct(): void
    {
        $products = [
            1 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'export' => true
            ]
        ];
        $this->setUpProductData($products);

        $this->deltaHandler->expects(self::never())->method('addDelete');
        $this->deltaHandler->expects(self::once())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');
        $this->object->runDelta(1, array_keys($products));
    }

    /**
     * Tests runDelta with a mix of valid / deleted products.
     */
    public function testRunDeltaMixed(): void
    {
        $products = [
            1 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH
            ],
            2 => [
                'status' => Status::STATUS_DISABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'export' => true
            ],
            3 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'export' => false
            ]
        ];
        $this->setUpProductData($products);

        $this->deltaHandler->expects(self::exactly(2))->method('addDelete');
        $this->deltaHandler->expects(self::once())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');

        $this->object->runDelta(1, array_keys($products));
    }

    /**
     * Tests runDelta with an exception
     */
    public function testRunDeltaError(): void
    {
        $products = [
            1 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH
            ],
            2 => [
                'status' => Status::STATUS_DISABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'export' => true
            ],
            3 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'export' => false
            ]
        ];
        $this->setUpProductData($products);

        $this->deltaHandler->expects(self::exactly(2))->method('addDelete');
        $this->deltaHandler->expects(self::once())->method('addData');
        $this->deltaHandler->method('send')->willThrowException(new \Exception('An Error'));
        $this->logger->expects(self::once())
            ->method('error')->with('PureClarity: Error processing product Deltas: An Error');
        $this->object->runDelta(1, array_keys($products));
    }
}
