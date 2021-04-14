<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Delta\Type;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PureClarity\Api\Delta\Type\ProductFactory;
use PureClarity\Api\Delta\Type\Product as DeltaHandler;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Delta\Type\Product;
use Pureclarity\Core\Api\ProductFeedRowDataManagementInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use ReflectionException;

/**
 * Class ProductTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Delta\Type\Product
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductTest extends TestCase
{
    /** @var Product $object */
    private $object;

    /** @var Emulation|MockObject */
    private $appEmulation;

    /** @var ProductCollectionFactory|MockObject */
    private $collectionFactory;

    /** @var Collection|MockObject */
    private $productCollection;

    /** @var MockObject|ProductFactory */
    private $deltaFactory;

    /** @var MockObject|DeltaHandler */
    private $deltaHandler;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|ProductFeedRowDataManagementInterface */
    private $productDataHandler;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var StoreManagerInterface|MockObject */
    private $storeManager;

    /**
     * @throws ReflectionException
     */
    protected function setUp() : void
    {
        $this->appEmulation = $this->createMock(Emulation::class);
        $this->collectionFactory = $this->createMock(ProductCollectionFactory::class);
        $this->productCollection = $this->createPartialMock(
            Collection::class,
            [
                'addAttributeToFilter',
                'setStoreId',
                'count',
                'getItems',
                'addStoreFilter',
                'addUrlRewrite',
                'addAttributeToSelect',
                'addMinimalPrice',
                'addTaxPercents'
            ]
        );
        $this->deltaFactory = $this->createMock(ProductFactory::class);
        $this->deltaHandler = $this->createMock(DeltaHandler::class);
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->productDataHandler = $this->createMock(ProductFeedRowDataManagementInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->collectionFactory->method('create')->willReturn($this->productCollection);
        $this->deltaFactory->method('create')->willReturn($this->deltaHandler);

        $this->object = new Product(
            $this->appEmulation,
            $this->collectionFactory,
            $this->deltaFactory,
            $this->coreConfig,
            $this->logger,
            $this->storeManager,
            $this->productDataHandler
        );
    }

    /**
     * Sets up a StoreInterface mock
     * @param bool $error
     * @return StoreInterface|MockObject
     * @throws ReflectionException
     */
    public function setupStore(bool $error = false)
    {
        $store = $this->createMock(StoreInterface::class);

        $store->method('getId')
            ->willReturn('1');

        if ($error) {
            $this->storeManager->expects(self::once())
                ->method('getStore')
                ->willThrowException(new NoSuchEntityException(new Phrase('An error')));
        } else {
            $this->storeManager->expects(self::once())
                ->method('getStore')
                ->willReturn($store);
        }

        return $store;
    }

    /**
     * Generates a mock product model.
     *
     * @param int $productId
     * @param array $prodInfo
     * @return MockObject|ProductModel
     */
    private function getProductModel(int $productId, array $prodInfo)
    {
        $model = $this->getMockBuilder(ProductModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getVisibility', 'getData'])
            ->getMock();

        $model->method('getId')
            ->willReturn($productId);

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
     * @param StoreInterface|MockObject $store
     * @param array $productData
     * @param bool $returnEmpty
     */
    public function setUpProductData($store, array $productData, $returnEmpty = false): void
    {
        $products = [];
        if ($returnEmpty === false) {
            $index = 0;
            foreach ($productData as $id => $product) {
                $model = $this->getProductModel($id, $product);
                $products[$id] = $model;
                if (isset($product['export'])) {
                    if ($product['export'] === true) {
                        $this->productDataHandler->expects(self::at($index))
                            ->method('getRowData')
                            ->with($store, $model)
                            ->willReturn(['somedata']);
                    } else {
                        $this->productDataHandler->expects(self::at($index))
                            ->method('getRowData')
                            ->with($store, $model)
                            ->willReturn([]);
                    }
                    $index++;
                }
            }
        }

        $this->productCollection->expects(self::at(0))->method('setStoreId')->with(1);
        $this->productCollection->expects(self::at(1))->method('addStoreFilter')->with($store);
        $this->productCollection->expects(self::at(2))->method('addUrlRewrite');
        $this->productCollection->expects(self::at(3))->method('addAttributeToSelect')->with('*');

        $this->productCollection->expects(self::at(4))
            ->method('addAttributeToFilter')
            ->with('entity_id', array_keys($productData));

        $this->productCollection->expects(self::at(5))->method('addMinimalPrice');
        $this->productCollection->expects(self::at(6))->method('addTaxPercents');

        if ($returnEmpty === false) {
            $this->productCollection->expects(self::at(7))->method('count')->willReturn(count($products));
            $this->productCollection->expects(self::at(8))->method('getItems')->willReturn($products);
        } else {
            $this->productCollection->expects(self::at(7))->method('count')->willReturn(count($productData));
            $this->productCollection->expects(self::at(8))->method('getItems')->willReturn([]);
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
     * Tests runDelta with a store exception
     * @throws ReflectionException
     */
    public function testRunDeltaStoreException(): void
    {
        $this->setupStore(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Error running product Deltas: An error');

        $this->appEmulation->expects(self::never())->method('startEnvironmentEmulation');
        $this->deltaHandler->expects(self::never())->method('addDelete');
        $this->deltaHandler->expects(self::never())->method('addData');
        $this->deltaHandler->expects(self::never())->method('send');

        $this->object->runDelta(1, [1]);
    }

    /**
     * Tests runDelta with no products loaded
     */
    public function testRunDeltaNoData(): void
    {
        $this->appEmulation->expects(self::never())->method('startEnvironmentEmulation');
        $this->storeManager->expects(self::never())->method('getStore');
        $this->deltaHandler->expects(self::never())->method('addDelete');
        $this->deltaHandler->expects(self::never())->method('addData');
        $this->deltaHandler->expects(self::never())->method('send');
        $this->object->runDelta(1, []);
    }

    /**
     * Tests runDelta with no enabled products available.
     * @throws ReflectionException
     */
    public function testRunDeltaOneDeletedProduct(): void
    {
        $store = $this->setupStore();
        $products = [
            1 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_NOT_VISIBLE
            ]
        ];
        $this->setUpProductData($store, $products, true);
        $this->deltaHandler->expects(self::once())->method('addDelete');
        $this->deltaHandler->expects(self::never())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');
        $this->object->runDelta(1, [1]);
    }

    /**
     * Tests runDelta with no visible products available.
     * @throws ReflectionException
     */
    public function testRunDeltaNoVisibleProducts(): void
    {
        $store = $this->setupStore();
        $products = [
            1 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_NOT_VISIBLE
            ]
        ];
        $this->setUpProductData($store, $products);
        $this->deltaHandler->expects(self::once())->method('addDelete');
        $this->deltaHandler->expects(self::never())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');
        $this->object->runDelta(1, [1]);
    }

    /**
     * Tests runDelta with no enabled products available.
     * @throws ReflectionException
     */
    public function testRunDeltaNoEnabledProducts(): void
    {
        $store = $this->setupStore();
        $products = [
            1 => [
                'status' => Status::STATUS_DISABLED,
                'visibility' => Visibility::VISIBILITY_BOTH
            ]
        ];
        $this->setUpProductData($store, $products);
        $this->deltaHandler->expects(self::once())->method('addDelete');
        $this->deltaHandler->expects(self::never())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');
        $this->object->runDelta(1, [1]);
    }

    /**
     * Tests runDelta with a product that does not get returned from the product export class.
     * @throws ReflectionException
     */
    public function testRunDeltaOneExcludedProduct(): void
    {
        $store = $this->setupStore();
        $products = [
            1 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'export' => false
            ]
        ];
        $this->setUpProductData($store, $products);

        $this->deltaHandler->expects(self::once())->method('addDelete');
        $this->deltaHandler->expects(self::never())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');
        $this->object->runDelta(1, [1]);
    }

    /**
     * Tests runDelta with a product that gets returned from the product export class correctly.
     * @throws ReflectionException
     */
    public function testRunDeltaOneProduct(): void
    {
        $store = $this->setupStore();
        $products = [
            1 => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'export' => true
            ]
        ];
        $this->setUpProductData($store, $products);

        $this->deltaHandler->expects(self::never())->method('addDelete');
        $this->deltaHandler->expects(self::once())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');
        $this->object->runDelta(1, array_keys($products));
    }

    /**
     * Tests runDelta with a mix of valid / deleted products.
     * @throws ReflectionException
     */
    public function testRunDeltaMixed(): void
    {
        $store = $this->setupStore();
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
        $this->setUpProductData($store, $products);

        $this->deltaHandler->expects(self::exactly(2))->method('addDelete');
        $this->deltaHandler->expects(self::once())->method('addData');
        $this->deltaHandler->expects(self::once())->method('send');

        $this->object->runDelta(1, array_keys($products));
    }

    /**
     * Tests runDelta with an exception
     * @throws ReflectionException
     */
    public function testRunDeltaError(): void
    {
        $store = $this->setupStore();
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
        $this->setUpProductData($store, $products);

        $this->deltaHandler->expects(self::exactly(2))->method('addDelete');
        $this->deltaHandler->expects(self::once())->method('addData');
        $this->deltaHandler->method('send')->willThrowException(new \Exception('An Error'));
        $this->logger->expects(self::once())
            ->method('error')->with('PureClarity: Error processing product Deltas: An Error');
        $this->object->runDelta(1, array_keys($products));
    }
}
