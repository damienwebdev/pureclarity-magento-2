<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Brand;

use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Brand\FeedData;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\CategoryRepository;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\LocalizedException;
use PureClarity\Api\Feed\Feed;
use ReflectionException;

/**
 * Class FeedDataTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Brand\FeedData
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

    /** @var MockObject|CategoryCollectionFactory */
    private $collectionFactory;

    /** @var MockObject|CategoryRepository */
    private $categoryRepository;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

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
            ->setMethods([
                'addAttributeToSelect',
                'addIdFilter',
                'clear',
                'getItems',
                'getLastPageNumber',
                'setCurPage',
                'setPageSize'
            ])
            ->getMock();

        $this->collectionFactory = $this->getMockBuilder(CategoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactory->method('create')
            ->willReturn($this->collection);

        $this->categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new FeedData(
            $this->logger,
            $this->feedError,
            $this->collectionFactory,
            $this->categoryRepository,
            $this->coreConfig
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
     * Sets up config value loading
     */
    public function setupConfig(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('getBrandParentCategory')
            ->with(self::STORE_ID)
            ->willReturn('1');
    }

    /**
     * Sets up brand collection
     * @param bool $error
     */
    public function setupParentCategory(bool $error = false): void
    {
        if ($error) {
            $this->categoryRepository->expects(self::once())
                ->method('get')
                ->with('1')
                ->willThrowException(new NoSuchEntityException(new Phrase('A Category Error')));
        } else {
            $parent = $this->getMockBuilder(Category::class)
                ->disableOriginalConstructor()
                ->setMethods(['getChildren'])
                ->getMock();

            $parent->method('getChildren')
                ->willReturn('1,2,3');

            $this->categoryRepository->expects(self::once())
                ->method('get')
                ->with('1')
                ->willReturn($parent);
        }
    }

    /**
     * Sets up brand collection
     * @param bool $error
     */
    public function setupCollection(bool $error = false): void
    {
        if ($error) {
            $this->collection->expects(self::at(0))
                ->method('addAttributeToSelect')
                ->with('name')
                ->willThrowException(new LocalizedException(new Phrase('An Attribute Error')));
        } else {
            $this->collection->expects(self::at(0))
                ->method('addAttributeToSelect')
                ->with('name');

            $this->collection->expects(self::at(1))
                ->method('addAttributeToSelect')
                ->with('image');

            $this->collection->expects(self::at(2))
                ->method('addAttributeToSelect')
                ->with('description');

            $this->collection->expects(self::at(3))
                ->method('addAttributeToSelect')
                ->with('pureclarity_category_image');

            $this->collection->expects(self::at(4))
                ->method('addAttributeToSelect')
                ->with('pureclarity_hide_from_feed');

            $this->collection->expects(self::at(5))
                ->method('addIdFilter')
                ->with('1,2,3');

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
        $this->setupConfig();
        $this->setupParentCategory();
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
        $this->setupConfig();
        $this->setupParentCategory();
        $this->setupCollection();

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
        $this->setupConfig();
        $this->setupParentCategory();
        $this->setupCollection(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load brands: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'brand', 'Could not load brands: An Attribute Error');

        $this->collection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getTotalPages($store);
    }

    /**
     * Tests that a category exception is handled
     * @throws ReflectionException
     */
    public function testGetTotalPagesCategoryException(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupParentCategory(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load brands: A Category Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, Feed::FEED_TYPE_BRAND, 'Could not load brands: A Category Error');

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
        $this->setupConfig();
        $this->setupParentCategory();
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
     * @throws ReflectionException
     */
    public function testGetPageDataWithDataPageTwo(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupParentCategory();
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
     * Tests that a collection exception is handled
     * @throws ReflectionException
     */
    public function testGetPageDataCollectionException(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupParentCategory();
        $this->setupCollection(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load brands: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'brand', 'Could not load brands: An Attribute Error');

        $this->collection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getPageData($store, 1);
    }

    /**
     * Tests that a category exception is handled
     * @throws ReflectionException
     */
    public function testGetPageDataCategoryException(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupParentCategory(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load brands: A Category Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, Feed::FEED_TYPE_BRAND, 'Could not load brands: A Category Error');

        $this->collection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getPageData($store, 1);
    }
}
