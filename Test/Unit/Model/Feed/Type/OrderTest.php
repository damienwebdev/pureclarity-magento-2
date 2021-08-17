<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PureClarity\Api\Feed\Type\OrderFactory;
use Pureclarity\Core\Api\OrderFeedDataManagementInterface;
use Pureclarity\Core\Api\OrderFeedRowDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use PureClarity\Api\Feed\Type\Order as OrderFeed;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;

/**
 * Class OrderTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Order
 */
class OrderTest extends TestCase
{
    /** @var Order */
    private $object;

    /** @var MockObject|OrderFactory */
    private $orderFeedFactory;

    /** @var MockObject|OrderFeedDataManagementInterface */
    private $feedDataHandler;

    /** @var MockObject|OrderFeedRowDataManagementInterface */
    private $rowDataHandler;

    protected function setUp(): void
    {
        $this->orderFeedFactory = $this->createMock(OrderFactory::class);
        $this->feedDataHandler = $this->createMock(OrderFeedDataManagementInterface::class);
        $this->rowDataHandler = $this->createMock(OrderFeedRowDataManagementInterface::class);

        $this->object = new Order(
            $this->orderFeedFactory,
            $this->feedDataHandler,
            $this->rowDataHandler
        );
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Order::class, $this->object);
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
        $feed = $this->createMock(OrderFeed::class);

        $this->orderFeedFactory->expects(self::once())
            ->method('create')
            ->with([
                'accessKey' => 'A',
                'secretKey' => 'B',
                'region' => 1
            ])
            ->willReturn($feed);

        $feedBuilder = $this->object->getFeedBuilder('A', 'B', 1);
        self::assertInstanceOf(OrderFeed::class, $feedBuilder);
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
     * Tests that isEnabled always returns false
     */
    public function testRequiresEmulation(): void
    {
        self::assertEquals(false, $this->object->requiresEmulation());
    }
}
