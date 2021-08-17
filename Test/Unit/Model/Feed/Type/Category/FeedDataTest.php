<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Category;

use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Category\FeedData;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use ReflectionException;

/**
 * Class FeedDataTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Category\FeedData
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

    /** @var MockObject|CategoryCollectionFactory */
    private $collectionFactory;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->feedError = $this->createMock(Error::class);
        $this->collection = $this->createPartialMock(
            Collection::class,
            [
                'addAttributeToSelect',
                'addUrlRewriteToResult',
                'clear',
                'getItems',
                'getLastPageNumber',
                'setCurPage',
                'setPageSize',
                'setStore'
            ]
        );

        $this->collectionFactory = $this->createMock(CategoryCollectionFactory::class);

        $this->collectionFactory->method('create')
            ->willReturn($this->collection);

        $this->object = new FeedData(
            $this->logger,
            $this->feedError,
            $this->collectionFactory
        );
    }

    /**
     * Sets up a StoreInterface and store manager getStore
     * @return StoreInterface|MockObject
     * @throws ReflectionException
     */
    public function setupStore()
    {
        $store = $this->createMock(StoreInterface::class);

        $store->method('getId')
            ->willReturn('1');

        $store->method('getWebsiteId')
            ->willReturn('12');

        return $store;
    }

    /**
     * Sets up category collection
     * @param MockObject|StoreInterface $store
     * @param bool $error
     */
    public function setupCollection($store, bool $error = false): void
    {
        $this->collection->expects(self::at(0))
            ->method('setStore')
            ->with($store);

        if ($error) {
            $this->collection->expects(self::at(1))
                ->method('addAttributeToSelect')
                ->with('name')
                ->willThrowException(new LocalizedException(new Phrase('An Attribute Error')));
        } else {
            $this->collection->expects(self::at(1))
                ->method('addAttributeToSelect')
                ->with('name');

            $this->collection->expects(self::at(2))
                ->method('addAttributeToSelect')
                ->with('is_active');

            $this->collection->expects(self::at(3))
                ->method('addAttributeToSelect')
                ->with('image');

            $this->collection->expects(self::at(4))
                ->method('addAttributeToSelect')
                ->with('description');

            $this->collection->expects(self::at(5))
                ->method('addAttributeToSelect')
                ->with('pureclarity_category_image');

            $this->collection->expects(self::at(6))
                ->method('addAttributeToSelect')
                ->with('pureclarity_hide_from_feed');

            $this->collection->expects(self::at(7))
                ->method('addUrlRewriteToResult');

            $this->collection->expects(self::at(8))
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
        $this->setupCollection($store, true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load categories: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'category', 'Could not load categories: An Attribute Error');

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
     * Tests that a collection exception is handled
     * @throws ReflectionException
     */
    public function testGetPageDataCollectionException(): void
    {
        $store = $this->setupStore();
        $this->setupCollection($store, true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load categories: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'category', 'Could not load categories: An Attribute Error');

        $this->collection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getPageData($store, 1);
    }
}
