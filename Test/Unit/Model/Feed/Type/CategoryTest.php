<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Category;
use PHPUnit\Framework\MockObject\MockObject;
use PureClarity\Api\Feed\Type\CategoryFactory;
use Pureclarity\Core\Api\CategoryFeedDataManagementInterface;
use Pureclarity\Core\Api\CategoryFeedRowDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use PureClarity\Api\Feed\Type\Category as CategoryFeed;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;

/**
 * Class CategoryTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Category
 */
class CategoryTest extends TestCase
{
    /** @var Category */
    private $object;

    /** @var MockObject|CategoryFactory */
    private $categoryFeedFactory;

    /** @var MockObject|CategoryFeedDataManagementInterface */
    private $feedDataHandler;

    /** @var MockObject|CategoryFeedRowDataManagementInterface */
    private $rowDataHandler;

    protected function setUp(): void
    {
        $this->categoryFeedFactory = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedDataHandler = $this->getMockBuilder(CategoryFeedDataManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rowDataHandler = $this->getMockBuilder(CategoryFeedRowDataManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Category(
            $this->categoryFeedFactory,
            $this->feedDataHandler,
            $this->rowDataHandler
        );
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Category::class, $this->object);
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
        $feed = $this->getMockBuilder(CategoryFeed::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryFeedFactory->expects(self::once())
            ->method('create')
            ->with([
                'accessKey' => 'A',
                'secretKey' => 'B',
                'region' => 1
            ])
            ->willReturn($feed);

        $feedBuilder = $this->object->getFeedBuilder('A', 'B', 1);
        self::assertInstanceOf(CategoryFeed::class, $feedBuilder);
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
