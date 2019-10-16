<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml\Dashboard;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\FeedStatus;
use Pureclarity\Core\Model\FeedStatus as FeedStatusModel;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class FeedStatusTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Dashboard\FeedStatus
 */
class FeedStatusTest extends TestCase
{
    /** @var FeedStatus $object */
    private $object;

    /** @var MockObject|FeedStatusModel $feedStatusModel */
    private $feedStatusModel;

    protected function setUp()
    {
        $this->feedStatusModel = $this->getMockBuilder(FeedStatusModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new FeedStatus(
            $this->feedStatusModel
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(FeedStatus::class, $this->object);
    }

    public function testGetAreFeedsInProgress()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getAreFeedsInProgress')
            ->with(['product', 'category', 'user', 'brand', 'orders'], 1)
            ->willReturn(true);

        $this->assertEquals(true, $this->object->getAreFeedsInProgress(1));
    }

    public function testGetAreFeedsInProgressFalse()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getAreFeedsInProgress')
            ->with(['product', 'category', 'user', 'brand', 'orders'], 1)
            ->willReturn(false);

        $this->assertEquals(false, $this->object->getAreFeedsInProgress(1));
    }
    public function testGetAreFeedsDisabled()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getAreFeedsDisabled')
            ->with(['product', 'category', 'user', 'brand', 'orders'], 1)
            ->willReturn(true);

        $this->assertEquals(true, $this->object->getAreFeedsDisabled(1));
    }

    public function testGetAreFeedsDisabledFalse()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getAreFeedsDisabled')
            ->with(['product', 'category', 'user', 'brand', 'orders'], 1)
            ->willReturn(false);

        $this->assertEquals(false, $this->object->getAreFeedsDisabled(1));
    }

    public function testIsFeedEnabled()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('product', 1)
            ->willReturn(['enabled' => true]);

        $this->assertEquals(true, $this->object->isFeedEnabled('product', 1));
    }

    public function testIsFeedEnabledFalse()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('product', 1)
            ->willReturn(['enabled' => false]);

        $this->assertEquals(false, $this->object->isFeedEnabled('product', 1));
    }

    public function testGetProductFeedStatusLabel()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('product', 1)
            ->willReturn(['label' => 'My Product Label']);

        $this->assertEquals('My Product Label', $this->object->getProductFeedStatusLabel(1));
    }

    public function testGetCategoryFeedStatusLabel()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('category', 1)
            ->willReturn(['label' => 'My Category Label']);

        $this->assertEquals('My Category Label', $this->object->getCategoryFeedStatusLabel(1));
    }

    public function testGetUserFeedStatusLabel()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('user', 1)
            ->willReturn(['label' => 'My User Label']);

        $this->assertEquals('My User Label', $this->object->getUserFeedStatusLabel(1));
    }

    public function testGetBrandFeedStatusLabel()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('brand', 1)
            ->willReturn(['label' => 'My Brand Label']);

        $this->assertEquals('My Brand Label', $this->object->getBrandFeedStatusLabel(1));
    }

    public function testGetOrdersFeedStatusLabel()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('orders', 1)
            ->willReturn(['label' => 'My Orders Label']);

        $this->assertEquals('My Orders Label', $this->object->getOrdersFeedStatusLabel(1));
    }

    public function testGetProductFeedStatusClass()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('product', 1)
            ->willReturn(['class' => 'product-class']);

        $this->assertEquals('product-class', $this->object->getProductFeedStatusClass(1));
    }

    public function testGetCategoryFeedStatusClass()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('category', 1)
            ->willReturn(['class' => 'category-class']);

        $this->assertEquals('category-class', $this->object->getCategoryFeedStatusClass(1));
    }

    public function testGetUserFeedStatusClass()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('user', 1)
            ->willReturn(['class' => 'user-class']);

        $this->assertEquals('user-class', $this->object->getUserFeedStatusClass(1));
    }

    public function testGetBrandFeedStatusClass()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('brand', 1)
            ->willReturn(['class' => 'brand-class']);

        $this->assertEquals('brand-class', $this->object->getBrandFeedStatusClass(1));
    }

    public function testGetOrdersFeedStatusClass()
    {
        $this->feedStatusModel->expects($this->once())
            ->method('getFeedStatus')
            ->with('orders', 1)
            ->willReturn(['class' => 'orders-class']);

        $this->assertEquals('orders-class', $this->object->getOrdersFeedStatusClass(1));
    }
}
