<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\User;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\User\FeedData;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use ReflectionException;

/**
 * Class FeedDataTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\User\FeedData
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

    /** @var MockObject|CustomerCollection */
    private $customerCollection;

    /** @var MockObject|CustomerCollectionFactory */
    private $customerCollectionFactory;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->feedError = $this->createMock(Error::class);
        $this->customerCollection = $this->createMock(CustomerCollection::class);
        $this->customerCollectionFactory = $this->createMock(CustomerCollectionFactory::class);

        $this->customerCollectionFactory->method('create')->willReturn($this->customerCollection);

        $this->object = new FeedData(
            $this->logger,
            $this->feedError,
            $this->customerCollectionFactory
        );
    }

    /**
     * Sets up a StoreInterface and store manager getStore
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
     * Sets up customer collection
     * @param bool $error
     */
    public function setupCustomerCollection(bool $error = false): void
    {
        if ($error) {
            $this->customerCollection->expects(self::once())
                ->method('addAttributeToFilter')
                ->with('website_id', ['eq' => 12])
                ->willThrowException(new LocalizedException(new Phrase('An Attribute Error')));
        } else {
            $this->customerCollection->expects(self::once())
                ->method('addAttributeToFilter')
                ->with('website_id', ['eq' => 12]);

            $this->customerCollection->expects(self::once())
                ->method('getTable')
                ->willReturn('customer_address_entity');

            $this->customerCollection->expects(self::once())
                ->method('joinTable')
                ->with(
                    ['cad' => 'customer_address_entity'],
                    'parent_id = entity_id',
                    ['city', 'region', 'country_id'],
                    '`cad`.entity_id=`e`.default_shipping OR cad.parent_id = e.entity_id',
                    'left'
                );

            $this->customerCollection->expects(self::once())
                ->method('groupByAttribute')
                ->with('entity_id');

            $this->customerCollection->expects(self::once())
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
        $this->setupCustomerCollection();

        $this->customerCollection->expects(self::once())
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
        $this->setupCustomerCollection();

        $this->customerCollection->expects(self::once())
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
        $this->setupCustomerCollection(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load users: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'user', 'Could not load users: An Attribute Error');

        $this->customerCollection->expects(self::never())
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
        $this->setupCustomerCollection();

        $this->customerCollection->expects(self::once())
            ->method('clear');

        $this->customerCollection->expects(self::once())
            ->method('setCurPage')
            ->with(1);

        $this->customerCollection->expects(self::once())
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
        $this->setupCustomerCollection();

        $this->customerCollection->expects(self::once())
            ->method('clear');

        $this->customerCollection->expects(self::once())
            ->method('setCurPage')
            ->with(2);

        $this->customerCollection->expects(self::once())
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
        $this->setupCustomerCollection(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load users: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'user', 'Could not load users: An Attribute Error');

        $this->customerCollection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getPageData($store, 1);
    }
}
