<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Delta;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\Runner as FeedRunner;
use Pureclarity\Core\Model\Delta\Runner;
use Pureclarity\Core\Model\Delta\Type\Product;
use Pureclarity\Core\Model\ProductFeed;
use Pureclarity\Core\Model\ResourceModel\ProductFeed as ProductFeedResourceModel;
use Pureclarity\Core\Model\ResourceModel\ProductFeed\CollectionFactory;
use Pureclarity\Core\Model\ResourceModel\ProductFeed\Collection;

/**
 * Class RunnerTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Delta\Runner
 */
class RunnerTest extends TestCase
{
    /** @var Runner $object */
    private $object;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var MockObject|CollectionFactory */
    private $deltaIndexCollectionFactory;

    /** @var MockObject|Collection */
    private $deltaIndexCollection;

    /** @var MockObject|FeedRunner */
    private $feedRunner;

    /** @var MockObject|ProductFeedResourceModel */
    private $deltaResourceModel;

    /** @var MockObject|Product */
    private $productDeltaRunner;

    protected function setUp() : void
    {
        $this->deltaIndexCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->deltaIndexCollection = $this->createMock(Collection::class);

        $this->deltaIndexCollectionFactory->method('create')->willReturn($this->deltaIndexCollection);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->feedRunner = $this->createMock(FeedRunner::class);
        $this->deltaResourceModel = $this->createMock(ProductFeedResourceModel::class);
        $this->productDeltaRunner = $this->createMock(Product::class);

        $this->object = new Runner(
            $this->deltaIndexCollectionFactory,
            $this->logger,
            $this->feedRunner,
            $this->deltaResourceModel,
            $this->productDeltaRunner
        );
    }

    /**
     * @param string $id
     * @return MockObject|ProductFeed
     */
    private function getProductFeedModel(string $id)
    {
        $model = $this->createPartialMock(
            ProductFeed::class,
            ['__call']
        );

        $model->method('__call')
            ->with('getProductId')
            ->willReturn($id);

        return $model;
    }

    /**
     * @param array $categoryData
     * @param array $productData
     * @param bool $error
     */
    public function setUpDeltaData(array $categoryData, array $productData, bool $error = false): void
    {
        $x = 0;
        $categoryDeltas = [];
        foreach ($categoryData as $id) {
            $model = $this->getProductFeedModel($id);
            $categoryDeltas[] = $model;
            if (!$error) {
                $this->deltaResourceModel->expects(self::at($x))->method('delete')->with($model);
            }
            $x++;
        }

        $productDeltas = [];
        foreach ($productData as $id) {
            $model = $this->getProductFeedModel($id);
            $productDeltas[] = $model;
            if (!$error) {
                $this->deltaResourceModel->expects(self::at($x))->method('delete')->with($model);
            }
            $x++;
        }

        $this->deltaIndexCollection->expects(self::at(1))->method('addFieldToFilter')->with('token', 'category');
        $this->deltaIndexCollection->expects(self::at(2))->method('getItems')->willReturn($categoryDeltas);
        $this->deltaIndexCollection->expects(self::at(4))->method('addFieldToFilter')->with('token', 'product');
        $this->deltaIndexCollection->expects(self::at(5))->method('getItems')->willReturn($productDeltas);
    }

    public function testInstance(): void
    {
        self::assertInstanceOf(Runner::class, $this->object);
    }

    /**
     * Tests runDelta with no delta data to process
     */
    public function testRunDeltasNoData(): void
    {
        $this->setUpDeltaData([], []);
        $this->object->runDeltas(1);
    }

    /**
     * Tests runDelta with a full category feed required
     */
    public function testRunDeltasCategoryFullFeed(): void
    {
        $this->setUpDeltaData(['-1'], []);
        $this->feedRunner->expects(self::once())->method('selectedFeeds')->with(1, ['category']);
        $this->object->runDeltas(1);
    }

    /**
     * Tests runDelta with a full product feed required
     */
    public function testRunDeltasProductFullFeed(): void
    {
        $this->setUpDeltaData([], ['-1']);
        $this->feedRunner->expects(self::once())->method('selectedFeeds')->with(1, ['product']);
        $this->object->runDeltas(1);
    }

    /**
     * Tests runDelta with product delta data to process
     */
    public function testRunDeltasProductDeltas(): void
    {
        $this->setUpDeltaData([], ['1', '2', '3']);
        $this->productDeltaRunner->expects(self::once())->method('runDelta')->with(1, ['1', '2', '3']);
        $this->object->runDeltas(1);
    }

    /**
     * Tests runDelta with an error occurring on cleanup
     */
    public function testRunDeltasDeleteError(): void
    {
        $this->deltaResourceModel->method('delete')
            ->willThrowException(new \Exception('An Error'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Error deleting delta rows: An Error');

        $this->setUpDeltaData([], ['1', '2', '3'], true);
        $this->productDeltaRunner->expects(self::once())->method('runDelta')->with(1, ['1', '2', '3']);
        $this->object->runDeltas(1);
    }
}
