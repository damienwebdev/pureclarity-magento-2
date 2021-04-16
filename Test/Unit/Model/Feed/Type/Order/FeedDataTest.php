<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Order;

use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use PureClarity\Api\Feed\Feed;
use Pureclarity\Core\Model\Feed\Type\Order\FeedData;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use ReflectionException;

/**
 * Class FeedDataTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Order\FeedData
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

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedError = $this->getMockBuilder(Error::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactory->method('create')->willReturn($this->collection);

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
     * Sets up the order collection
     * @param bool $error
     */
    public function setupCollection(bool $error = false): void
    {
        if ($error) {
            $this->collection->expects(self::once())
                ->method('addAttributeToFilter')
                ->with('store_id', self::STORE_ID)
                ->willThrowException(new LocalizedException(new Phrase('An Attribute Error')));
        } else {
            $this->collection->expects(self::at(0))
                ->method('addAttributeToFilter')
                ->with('store_id', self::STORE_ID);

            $this->collection->expects(self::at(1))
                ->method('addAttributeToFilter')
                ->with('created_at');

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
     */
    public function testGetTotalPagesNoData(): void
    {
        $store = $this->setupStore();
        $this->setupCollection();

        $this->collection->expects(self::once())
            ->method('getLastPageNumber')
            ->willReturn(0);

        $pages = $this->object->getTotalPages($store);
        self::assertEquals(0, $pages);
    }

    /**
     * Tests that the total number of pages is returned correctly when there are some pages
     */
    public function testGetTotalPagesWithData(): void
    {
        $store = $this->setupStore();
        $this->setupCollection();

        $this->collection->expects(self::once())
            ->method('getLastPageNumber')
            ->willReturn(5);

        $pages = $this->object->getTotalPages($store);
        self::assertEquals(5, $pages);
    }

    /**
     * Tests that a collection exception is handled
     */
    public function testGetTotalPagesCollectionException(): void
    {
        $store = $this->setupStore();
        $this->setupCollection(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load orders: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, Feed::FEED_TYPE_ORDER, 'Could not load orders: An Attribute Error');

        $this->collection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getTotalPages($store);
    }

    /**
     * Tests that getPageData returns a page of data
     */
    public function testGetPageDataWithData(): void
    {
        $store = $this->setupStore();
        $this->setupCollection();

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
     */
    public function testGetPageDataWithDataPageTwo(): void
    {
        $store = $this->setupStore();
        $this->setupCollection();

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
     * Tests that a store load exception is handled
     */
    public function testGetPageDataCollectionException(): void
    {
        $store = $this->setupStore();
        $this->setupCollection(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load orders: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, Feed::FEED_TYPE_ORDER, 'Could not load orders: An Attribute Error');

        $this->collection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getPageData($store, 1);
    }
}
