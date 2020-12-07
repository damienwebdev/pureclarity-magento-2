<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\Block\Adminhtml\Toolbar;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\State;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;

/**
 * Class ToolbarTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Toolbar
 */
class ToolbarTest extends TestCase
{
    /** @var Toolbar $object */
    private $object;

    protected function setUp()
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

        $this->object = new Toolbar(
            $context,
            $stateViewModel,
            $storesViewModel
        );
    }

    public function testInstance()
    {
        self::assertInstanceOf(Toolbar::class, $this->object);
    }

    public function testTemplate()
    {
        self::assertInstanceOf(Template::class, $this->object);
    }

    public function testGetPureclarityStateViewModel()
    {
        self::assertInstanceOf(State::class, $this->object->getPureclarityStateViewModel());
    }

    public function testGetPureclarityStoresViewModel()
    {
        self::assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }
}
