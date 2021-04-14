<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml\Dashboard;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\FeedStatus;
use Pureclarity\Core\Model\Feed\Status;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

/**
 * Class FeedStatusTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Dashboard\FeedStatus
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeedStatusTest extends TestCase
{
    /** @var FeedStatus $object */
    private $object;

    /** @var MockObject|Status $feedStatusModel */
    private $feedStatusModel;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->feedStatusModel = $this->createMock(Status::class);

        $this->object = new FeedStatus(
            $this->feedStatusModel
        );
    }

    public function testInstance(): void
    {
        self::assertInstanceOf(FeedStatus::class, $this->object);
    }

    public function testGetAreFeedsInProgress(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('areFeedsInProgress')
            ->with(['product', 'category', 'user', 'brand', 'orders'], 1)
            ->willReturn(true);

        self::assertEquals(true, $this->object->areFeedsInProgress(1));
    }

    public function testGetAreFeedsInProgressFalse(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('areFeedsInProgress')
            ->with(['product', 'category', 'user', 'brand', 'orders'], 1)
            ->willReturn(false);

        self::assertEquals(false, $this->object->areFeedsInProgress(1));
    }
    public function testGetAreFeedsDisabled(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('areFeedsDisabled')
            ->with(['product', 'category', 'user', 'brand', 'orders'], 1)
            ->willReturn(true);

        self::assertEquals(true, $this->object->areFeedsDisabled(1));
    }

    public function testGetAreFeedsDisabledFalse(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('areFeedsDisabled')
            ->with(['product', 'category', 'user', 'brand', 'orders'], 1)
            ->willReturn(false);

        self::assertEquals(false, $this->object->areFeedsDisabled(1));
    }

    public function testIsFeedEnabled(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('product', 1)
            ->willReturn(['enabled' => true]);

        self::assertEquals(true, $this->object->isFeedEnabled('product', 1));
    }

    public function testIsFeedEnabledFalse(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('product', 1)
            ->willReturn(['enabled' => false]);

        self::assertEquals(false, $this->object->isFeedEnabled('product', 1));
    }

    public function testGetProductFeedStatusLabel(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('product', 1)
            ->willReturn(['label' => 'My Product Label']);

        self::assertEquals('My Product Label', $this->object->getProductFeedStatusLabel(1));
    }

    public function testGetCategoryFeedStatusLabel(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('category', 1)
            ->willReturn(['label' => 'My Category Label']);

        self::assertEquals('My Category Label', $this->object->getCategoryFeedStatusLabel(1));
    }

    public function testGetUserFeedStatusLabel(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('user', 1)
            ->willReturn(['label' => 'My User Label']);

        self::assertEquals('My User Label', $this->object->getUserFeedStatusLabel(1));
    }

    public function testGetBrandFeedStatusLabel(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('brand', 1)
            ->willReturn(['label' => 'My Brand Label']);

        self::assertEquals('My Brand Label', $this->object->getBrandFeedStatusLabel(1));
    }

    public function testGetOrdersFeedStatusLabel(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('orders', 1)
            ->willReturn(['label' => 'My Orders Label']);

        self::assertEquals('My Orders Label', $this->object->getOrdersFeedStatusLabel(1));
    }

    public function testGetProductFeedStatusClass(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('product', 1)
            ->willReturn(['class' => 'product-class']);

        self::assertEquals('product-class', $this->object->getProductFeedStatusClass(1));
    }

    public function testGetCategoryFeedStatusClass(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('category', 1)
            ->willReturn(['class' => 'category-class']);

        self::assertEquals('category-class', $this->object->getCategoryFeedStatusClass(1));
    }

    public function testGetUserFeedStatusClass(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('user', 1)
            ->willReturn(['class' => 'user-class']);

        self::assertEquals('user-class', $this->object->getUserFeedStatusClass(1));
    }

    public function testGetBrandFeedStatusClass(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('brand', 1)
            ->willReturn(['class' => 'brand-class']);

        self::assertEquals('brand-class', $this->object->getBrandFeedStatusClass(1));
    }

    public function testGetOrdersFeedStatusClass(): void
    {
        $this->feedStatusModel->expects(self::once())
            ->method('getFeedStatus')
            ->with('orders', 1)
            ->willReturn(['class' => 'orders-class']);

        self::assertEquals('orders-class', $this->object->getOrdersFeedStatusClass(1));
    }
}
