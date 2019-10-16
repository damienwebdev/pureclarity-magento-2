<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\Block\Adminhtml\Dashboard;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\State;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DashboardTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Dashboard
 */
class DashboardTest extends TestCase
{
    /** @var Dashboard $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|State $stateViewModel */
    private $stateViewModel;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateViewModel = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Dashboard(
            $this->context,
            $this->stateViewModel
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Dashboard::class, $this->object);
    }

    public function testTemplate()
    {
        $this->assertInstanceOf(Template::class, $this->object);
    }

    public function testGetPureclarityStateViewModel()
    {
        $this->assertInstanceOf(State::class, $this->object->getPureclarityStateViewModel());
    }
}
