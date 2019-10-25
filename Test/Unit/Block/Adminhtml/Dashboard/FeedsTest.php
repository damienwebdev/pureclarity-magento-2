<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\Block\Adminhtml\Dashboard\Feeds;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\FeedStatus;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class FeedsTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Dashboard\Feeds
 */
class FeedsTest extends TestCase
{
    /** @var Feeds $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|FeedStatus $feedStatusViewModel */
    private $feedStatusViewModel;

    /** @var MockObject|Stores $storesViewModel */
    private $storesViewModel;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedStatusViewModel = $this->getMockBuilder(FeedStatus::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storesViewModel = $this->getMockBuilder(Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Feeds(
            $this->context,
            $this->feedStatusViewModel,
            $this->storesViewModel
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Feeds::class, $this->object);
    }

    public function testTemplate()
    {
        $this->assertInstanceOf(Template::class, $this->object);
    }

    public function testGetPureclarityFeedStatusViewModel()
    {
        $this->assertInstanceOf(FeedStatus::class, $this->object->getPureclarityFeedStatusViewModel());
    }

    public function testGetPureclarityStoresViewModel()
    {
        $this->assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }
}
