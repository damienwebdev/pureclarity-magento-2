<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PureClarity\Api\Feed\Type\ProductFactory;
use Pureclarity\Core\Api\ProductFeedDataManagementInterface;
use Pureclarity\Core\Api\ProductFeedRowDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use PureClarity\Api\Feed\Feed;
use PureClarity\Api\Feed\Type\Product as ProductFeed;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;

/**
 * Class ProductTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Product
 */
class ProductTest extends TestCase
{
    /** @var Product */
    private $object;

    /** @var MockObject|ProductFactory */
    private $productFeedFactory;

    /** @var MockObject|ProductFeedDataManagementInterface */
    private $feedDataHandler;

    /** @var MockObject|ProductFeedRowDataManagementInterface */
    private $rowDataHandler;

    protected function setUp(): void
    {
        $this->productFeedFactory = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedDataHandler = $this->getMockBuilder(ProductFeedDataManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rowDataHandler = $this->getMockBuilder(ProductFeedRowDataManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Product(
            $this->productFeedFactory,
            $this->feedDataHandler,
            $this->rowDataHandler
        );
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Product::class, $this->object);
    }

    /**
     * Tests the class implements the right interface
     */
    public function testImplements(): void
    {
        self::assertInstanceOf(FeedManagementInterface::class, $this->object);
    }

    /**
     * Tests that isEnabled always returns true
     */
    public function testIsEnabled(): void
    {
        self::assertEquals(true, $this->object->isEnabled(1));
    }

    /**
     * Tests that getFeedBuilder passes the right info to the feed builder factory class
     */
    public function testGetFeedBuilder(): void
    {
        $feed = $this->getMockBuilder(ProductFeed::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productFeedFactory->expects(self::once())
            ->method('create')
            ->with([
                'accessKey' => 'A',
                'secretKey' => 'B',
                'region' => 1
            ])
            ->willReturn($feed);

        $feedBuilder = $this->object->getFeedBuilder('A', 'B', 1);
        self::assertInstanceOf(ProductFeed::class, $feedBuilder);
    }

    /**
     * Tests getFeedDataHandler returns the right class
     */
    public function testGetFeedDataHandler(): void
    {
        self::assertInstanceOf(FeedDataManagementInterface::class, $this->object->getFeedDataHandler());
    }

    /**
     * Tests getRowDataHandler returns the right class
     */
    public function testGetRowDataHandler(): void
    {
        self::assertInstanceOf(FeedRowDataManagementInterface::class, $this->object->getRowDataHandler());
    }

    /**
     * Tests that requiresEmulation always returns true
     */
    public function testRequiresEmulation(): void
    {
        self::assertEquals(true, $this->object->requiresEmulation());
    }
}
