<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\Block\Adminhtml\Dashboard\Configured;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\State;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ConfiguredTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Dashboard\Configured
 */
class ConfiguredTest extends TestCase
{
    /** @var Configured $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|State $state */
    private $state;

    /** @var MockObject|Stores $stores */
    private $stores;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->state = $this->createMock(State::class);
        $this->stores = $this->createMock(Stores::class);

        $this->object = new Configured(
            $this->context,
            $this->state,
            $this->stores
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Configured::class, $this->object);
    }

    public function testTemplate()
    {
        $this->assertInstanceOf(Template::class, $this->object);
    }

    public function testGetPureclarityStateViewModel()
    {
        $this->assertInstanceOf(State::class, $this->object->getPureclarityStateViewModel());
    }

    public function testGetPureclarityStoresViewModel()
    {
        $this->assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }
}
