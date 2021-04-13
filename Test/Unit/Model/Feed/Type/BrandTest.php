<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed\Type\Brand;
use PHPUnit\Framework\MockObject\MockObject;
use PureClarity\Api\Feed\Type\BrandFactory;
use Pureclarity\Core\Api\BrandFeedDataManagementInterface;
use Pureclarity\Core\Api\BrandFeedRowDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use PureClarity\Api\Feed\Type\Brand as BrandFeed;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;

/**
 * Class BrandTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Brand
 */
class BrandTest extends TestCase
{
    /** @var Brand */
    private $object;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|BrandFactory */
    private $brandFeedFactory;

    /** @var MockObject|BrandFeedDataManagementInterface */
    private $feedDataHandler;

    /** @var MockObject|BrandFeedRowDataManagementInterface */
    private $rowDataHandler;

    protected function setUp(): void
    {
        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->brandFeedFactory = $this->getMockBuilder(BrandFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedDataHandler = $this->getMockBuilder(BrandFeedDataManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rowDataHandler = $this->getMockBuilder(BrandFeedRowDataManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Brand(
            $this->coreConfig,
            $this->brandFeedFactory,
            $this->feedDataHandler,
            $this->rowDataHandler
        );
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Brand::class, $this->object);
    }

    /**
     * Tests the class implements the right interface
     */
    public function testImplements(): void
    {
        self::assertInstanceOf(FeedManagementInterface::class, $this->object);
    }

    /**
     * Tests that isEnabled returns false if disabled & brand selected
     */
    public function testIsEnabledReturnsFalseOnDisabled(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isBrandFeedEnabled')
            ->with(1)
            ->willReturn(false);

        $this->coreConfig->expects(self::once())
            ->method('getBrandParentCategory')
            ->with(1)
            ->willReturn(1);

        self::assertEquals(false, $this->object->isEnabled(1));
    }

    /**
     * Tests that isEnabled returns false if enabled & brand not selected
     */
    public function testIsEnabledReturnsFalseOnNoBrand(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isBrandFeedEnabled')
            ->with(1)
            ->willReturn(true);

        $this->coreConfig->expects(self::once())
            ->method('getBrandParentCategory')
            ->with(1)
            ->willReturn(false);

        self::assertEquals(false, $this->object->isEnabled(1));
    }

    /**
     * Tests that isEnabled returns false if enabled & brand not selected
     */
    public function testIsEnabledReturnsFalseOnBadBrand(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isBrandFeedEnabled')
            ->with(1)
            ->willReturn(true);

        $this->coreConfig->expects(self::once())
            ->method('getBrandParentCategory')
            ->with(1)
            ->willReturn('-1');

        self::assertEquals(false, $this->object->isEnabled(1));
    }

    /**
     * Tests that isEnabled returns true if enabled & brand selected
     */
    public function testIsEnabledReturnsTrue(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isBrandFeedEnabled')
            ->with(1)
            ->willReturn(true);

        $this->coreConfig->expects(self::once())
            ->method('getBrandParentCategory')
            ->with(1)
            ->willReturn(1);

        self::assertEquals(true, $this->object->isEnabled(1));
    }

    /**
     * Tests that getFeedBuilder passes the right info to the feed builder factory class
     */
    public function testGetFeedBuilder(): void
    {
        $feed = $this->getMockBuilder(BrandFeed::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->brandFeedFactory->expects(self::once())
            ->method('create')
            ->with([
                'accessKey' => 'A',
                'secretKey' => 'B',
                'region' => 1
            ])
            ->willReturn($feed);

        $feedBuilder = $this->object->getFeedBuilder('A', 'B', 1);
        self::assertInstanceOf(BrandFeed::class, $feedBuilder);
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
