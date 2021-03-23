<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\User;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\User\FeedData;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\Error;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

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

    /** @var MockObject|StoreManagerInterface */
    private $storeManager;

    /** @var MockObject|CustomerCollection */
    private $customerCollection;

    /** @var MockObject|CustomerCollectionFactory */
    private $customerCollectionFactory;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedError = $this->getMockBuilder(Error::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerCollection = $this->getMockBuilder(CustomerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerCollectionFactory = $this->getMockBuilder(CustomerCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerCollectionFactory->method('create')->willReturn($this->customerCollection);

        $this->object = new FeedData(
            $this->logger,
            $this->feedError,
            $this->storeManager,
            $this->customerCollectionFactory
        );
    }

    /**
     * Sets up a StoreInterface and store manager getStore
     * @param bool $error
     */
    public function setupStore(bool $error = false): void
    {
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store->method('getId')
            ->willReturn('1');

        $store->method('getWebsiteId')
            ->willReturn('12');

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
     */
    public function testGetTotalPagesNoData(): void
    {
        $this->setupStore();
        $this->setupCustomerCollection();

        $this->customerCollection->expects(self::once())
            ->method('getLastPageNumber')
            ->willReturn(0);

        $pages = $this->object->getTotalPages(self::STORE_ID);
        self::assertEquals(0, $pages);
    }

    /**
     * Tests that the total number of pages is returned correctly when there are some pages
     */
    public function testGetTotalPagesWithData(): void
    {
        $this->setupStore();
        $this->setupCustomerCollection();

        $this->customerCollection->expects(self::once())
            ->method('getLastPageNumber')
            ->willReturn(5);

        $pages = $this->object->getTotalPages(self::STORE_ID);
        self::assertEquals(5, $pages);
    }

    /**
     * Tests that a store load exception is handled
     */
    public function testGetTotalPagesStoreException(): void
    {
        $this->setupStore(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load users: An Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'user', 'Could not load users: An Error');

        $this->customerCollection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getTotalPages(self::STORE_ID);
    }

    /**
     * Tests that a collection exception is handled
     */
    public function testGetTotalPagesCollectionException(): void
    {
        $this->setupStore();
        $this->setupCustomerCollection(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load users: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'user', 'Could not load users: An Attribute Error');

        $this->customerCollection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getTotalPages(self::STORE_ID);
    }

    /**
     * Tests that getPageData returns a page of data
     */
    public function testGetPageDataWithData(): void
    {
        $this->setupStore();
        $this->setupCustomerCollection();

        $this->customerCollection->expects(self::once())
            ->method('clear');

        $this->customerCollection->expects(self::once())
            ->method('setCurPage')
            ->with(1);

        $this->customerCollection->expects(self::once())
            ->method('getItems')
            ->willReturn([1,2]);

        $data = $this->object->getPageData(self::STORE_ID, 1);
        self::assertEquals([1,2], $data);
    }

    /**
     * Tests that getPageData returns a different page of data
     */
    public function testGetPageDataWithDataPageTwo(): void
    {
        $this->setupStore();
        $this->setupCustomerCollection();

        $this->customerCollection->expects(self::once())
            ->method('clear');

        $this->customerCollection->expects(self::once())
            ->method('setCurPage')
            ->with(2);

        $this->customerCollection->expects(self::once())
            ->method('getItems')
            ->willReturn([3,4]);

        $data = $this->object->getPageData(self::STORE_ID, 2);
        self::assertEquals([3,4], $data);
    }

    /**
     * Tests that a store load exception is handled
     */
    public function testGetPageDataStoreException(): void
    {
        $this->setupStore(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load users: An Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'user', 'Could not load users: An Error');

        $this->customerCollection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getPageData(self::STORE_ID, 1);
    }

    /**
     * Tests that a store load exception is handled
     */
    public function testGetPageDataCollectionException(): void
    {
        $this->setupStore();
        $this->setupCustomerCollection(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load users: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'user', 'Could not load users: An Attribute Error');

        $this->customerCollection->expects(self::never())
            ->method('getLastPageNumber');

        $this->object->getPageData(self::STORE_ID, 1);
    }
}
