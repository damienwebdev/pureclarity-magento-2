<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\TypeHandler;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\Feed\Type\ProductFactory;
use Pureclarity\Core\Model\Feed\Type\CategoryFactory;
use Pureclarity\Core\Model\Feed\Type\BrandFactory;
use Pureclarity\Core\Model\Feed\Type\UserFactory;
use Pureclarity\Core\Model\Feed\Type\OrderFactory;
use Pureclarity\Core\Model\Feed\Type\Product;
use Pureclarity\Core\Model\Feed\Type\Category;
use Pureclarity\Core\Model\Feed\Type\Brand;
use Pureclarity\Core\Model\Feed\Type\User;
use Pureclarity\Core\Model\Feed\Type\Order;
use PureClarity\Api\Feed\Feed;

/**
 * Class TypeHandlerTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\TypeHandler
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TypeHandlerTest extends TestCase
{
    /** @var TypeHandler */
    private $object;

    /** @var MockObject|ProductFactory */
    private $productFeed;

    /** @var MockObject|CategoryFactory */
    private $categoryFeed;

    /** @var MockObject|BrandFactory */
    private $brandFeed;

    /** @var MockObject|UserFactory */
    private $userFeed;

    /** @var MockObject|OrderFactory */
    private $orderFeed;

    protected function setUp(): void
    {
        $this->productFeed = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryFeed = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->brandFeed = $this->getMockBuilder(BrandFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userFeed = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderFeed = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new TypeHandler(
            $this->productFeed,
            $this->categoryFeed,
            $this->brandFeed,
            $this->userFeed,
            $this->orderFeed
        );
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(TypeHandler::class, $this->object);
    }

    /**
     * Tests that an error is thrown if an invalid feed type is passed
     */
    public function testGetFeedHandlerInvalid(): void
    {
        $this->userFeed->expects(self::never())
            ->method('create');

        try {
            $this->object->getFeedHandler('fish');
        } catch (\Exception $e) {
            self::assertEquals('PureClarity feed type not recognised: fish', $e->getMessage());
        }
    }

    /**
     * Tests that a product feed class is returned correctly
     */
    public function testGetFeedHandlerProduct(): void
    {
        $feed = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productFeed->expects(self::once())
            ->method('create')
            ->willReturn($feed);

        $handler = $this->object->getFeedHandler(Feed::FEED_TYPE_PRODUCT);
        self::assertInstanceOf(Product::class, $handler);
    }

    /**
     * Tests that a category feed class is returned correctly
     */
    public function testGetFeedHandlerCategory(): void
    {
        $feed = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryFeed->expects(self::once())
            ->method('create')
            ->willReturn($feed);

        $handler = $this->object->getFeedHandler(Feed::FEED_TYPE_CATEGORY);
        self::assertInstanceOf(Category::class, $handler);
    }

    /**
     * Tests that a brand feed class is returned correctly
     */
    public function testGetFeedHandlerBrand(): void
    {
        $feed = $this->getMockBuilder(Brand::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->brandFeed->expects(self::once())
            ->method('create')
            ->willReturn($feed);

        $handler = $this->object->getFeedHandler(Feed::FEED_TYPE_BRAND);
        self::assertInstanceOf(Brand::class, $handler);
    }

    /**
     * Tests that a user feed class is returned correctly
     */
    public function testGetFeedHandlerUser(): void
    {
        $userFeed = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userFeed->expects(self::once())
            ->method('create')
            ->willReturn($userFeed);

        $handler = $this->object->getFeedHandler(Feed::FEED_TYPE_USER);
        self::assertInstanceOf(User::class, $handler);
    }

    /**
     * Tests that a order feed class is returned correctly
     */
    public function testGetFeedHandlerOrder(): void
    {
        $orderFeed = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderFeed->expects(self::once())
            ->method('create')
            ->willReturn($orderFeed);

        $handler = $this->object->getFeedHandler(Feed::FEED_TYPE_ORDER);
        self::assertInstanceOf(Order::class, $handler);
    }
}
