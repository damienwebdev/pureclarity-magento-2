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

/**
 * Class ConfiguredTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class ConfiguredTest extends TestCase
{
    /** @var Configured $object */
    private $object;

    /** @var Context $context */
    private $context;

    /** @var State $state */
    private $state;

    /** @var Stores $stores */
    private $stores;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stores = $this->getMockBuilder(Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

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
