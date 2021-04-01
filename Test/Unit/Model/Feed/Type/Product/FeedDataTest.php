<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Product;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Product\FeedData;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use ReflectionException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Class FeedDataTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Product\FeedData
 * @see \Pureclarity\Core\Model\Feed\Type\Product\FeedData
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FeedDataTest extends TestCase
{
    /** @var int */
    private const STORE_ID = 1;

    /** @var FeedData */
    private $object;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var MockObject|Error */
    private $feedError;

    /** @var MockObject|Collection */
    private $collection;

    /** @var MockObject|CollectionFactory */
    private $collectionFactory;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->feedError = $this->createMock(Error::class);
        $this->collection = $this->createPartialMock(
            Collection::class,
            [
                'addAttributeToFilter',
                'addAttributeToSelect',
                'addFieldToFilter',
                'addMinimalPrice',
                'addStoreFilter',
                'addTaxPercents',
                'addUrlRewrite',
                'clear',
                'getItems',
                'getLastPageNumber',
                'setCurPage',
                'setPageSize',
                'setStoreId'
            ]
        );

        $this->collectionFactory = $this->createMock(CollectionFactory::class);

        $this->collectionFactory->method('create')
            ->willReturn($this->collection);

        $this->object = new FeedData(
            $this->logger,
            $this->feedError,
            $this->collectionFactory
        );
    }

    /**
     * Sets up a StoreInterface
     *
     * @return StoreInterface|MockObject
     * @throws ReflectionException
     */
    public function setupStore()
    {
        $store = $this->createMock(StoreInterface::class);

        $store->method('getId')
            ->willReturn('1');

        return $store;
    }

    /**
     * Sets up product collection
     * @param StoreInterface|MockObject $store
     * @param string $error
     */
    public function setupCollection($store, string $error = ''): void
    {
        $this->collection->expects(self::once())
            ->method('setStoreId')
            ->with(self::STORE_ID);

        if ($error === 'attribute_error') {
            $this->collection->expects(self::once())
                ->method('addUrlRewrite');

            $this->collection->expects(self::once())
                ->method('addStoreFilter')
                ->with($store);

            $this->collection->expects(self::once())
                ->method('addAttributeToSelect')
                ->with('*')
                ->willThrowException(new LocalizedException(new Phrase('An Attribute Error')));
        } else {

            $this->collection->expects(self::once())
                ->method('addUrlRewrite');

            $this->collection->expects(self::once())
                ->method('addStoreFilter')
                ->with($store);

            $this->collection->expects(self::once())
                ->method('addUrlRewrite');

            $this->collection->expects(self::once())
                ->method('addAttributeToSelect')
                ->with('*');

            $this->collection->expects(self::once())
                ->method('addAttributeToFilter')
                ->with('status', ['eq' => Status::STATUS_ENABLED]);

            $this->collection->expects(self::once())
                ->method('addFieldToFilter')
                ->with('visibility', [
                    'in' => [
                        Visibility::VISIBILITY_BOTH,
                        Visibility::VISIBILITY_IN_CATALOG,
                        Visibility::VISIBILITY_IN_SEARCH
                    ]
                ]);

            $this->collection->expects(self::once())
                ->method('addMinimalPrice');

            $this->collection->expects(self::once())
                ->method('addTaxPercents');

            $this->collection->expects(self::once())
                ->method('setPageSize')
                ->with(50);
        }
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(FeedData::class, $this->object);
    }

    /**
     * Tests that the page size is returned correctly
     */
    public function testGetPageSize(): void
    {
        $size = $this->object->getPageSize();
        self::assertEquals(50, $size);
    }

    /**
     * Tests that the total number of pages is returned correctly when no pages present
     * @throws ReflectionException
     */
    public function testGetTotalPagesNoData(): void
    {
        $store = $this->setupStore();
        $this->setupCollection($store);

        $this->collection->expects(self::once())
            ->method('getLastPageNumber')
            ->willReturn(0);

        $pages = $this->object->getTotalPages($store);
        self::assertEquals(0, $pages);
    }

    /**
     * Tests that the total number of pages is returned correctly when there are some pages
     * @throws ReflectionException
     */
    public function testGetTotalPagesWithData(): void
    {
        $store = $this->setupStore();
        $this->setupCollection($store);

        $this->collection->expects(self::once())
            ->method('getLastPageNumber')
            ->willReturn(5);

        $pages = $this->object->getTotalPages($store);
        self::assertEquals(5, $pages);
    }

    /**
     * Tests that a collection exception is handled
     * @throws ReflectionException
     */
    public function testGetTotalPagesCollectionException(): void
    {
        $store = $this->setupStore();
        $this->setupCollection($store, 'attribute_error');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load products: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'product', 'Could not load products: An Attribute Error');

        $this->collection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getTotalPages($store);
    }

    /**
     * Tests that getPageData returns a page of data
     * @throws ReflectionException
     */
    public function testGetPageDataWithData(): void
    {
        $store = $this->setupStore();
        $this->setupCollection($store);

        $this->collection->expects(self::once())
            ->method('clear');

        $this->collection->expects(self::once())
            ->method('setCurPage')
            ->with(1);

        $this->collection->expects(self::once())
            ->method('getItems')
            ->willReturn([1,2]);

        $data = $this->object->getPageData($store, 1);
        self::assertEquals([1,2], $data);
    }

    /**
     * Tests that getPageData returns a different page of data
     * @throws ReflectionException
     */
    public function testGetPageDataWithDataPageTwo(): void
    {
        $store = $this->setupStore();
        $this->setupCollection($store);

        $this->collection->expects(self::once())
            ->method('clear');

        $this->collection->expects(self::once())
            ->method('setCurPage')
            ->with(2);

        $this->collection->expects(self::once())
            ->method('getItems')
            ->willReturn([3,4]);

        $data = $this->object->getPageData($store, 2);
        self::assertEquals([3,4], $data);
    }

    /**
     * Tests that a collection load exception is handled
     * @throws ReflectionException
     */
    public function testGetPageDataCollectionException(): void
    {
        $store = $this->setupStore();
        $this->setupCollection($store, 'attribute_error');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load products: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'product', 'Could not load products: An Attribute Error');

        $this->collection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getPageData($store, 1);
    }
}
