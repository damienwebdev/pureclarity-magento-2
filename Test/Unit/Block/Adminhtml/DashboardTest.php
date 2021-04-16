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
use Pureclarity\Core\ViewModel\Adminhtml\Stores;

/**
 * Class DashboardTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Dashboard
 */
class DashboardTest extends TestCase
{
    /** @var Dashboard $object */
    private $object;

    protected function setUp(): void
    {
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stateViewModel = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storesViewModel = $this->getMockBuilder(Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Dashboard(
            $context,
            $stateViewModel,
            $storesViewModel
        );
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(Dashboard::class, $this->object);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testTemplate()
    {
        self::assertInstanceOf(Template::class, $this->object);
    }

    /**
     * Tests that getPureclarityStateViewModel returns the right class
     */
    public function testGetPureclarityStateViewModel()
    {
        self::assertInstanceOf(State::class, $this->object->getPureclarityStateViewModel());
    }

    /**
     * Tests that getPureclarityStoresViewModel returns the right class
     */
    public function testGetPureclarityStoresViewModel()
    {
        self::assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }
}
