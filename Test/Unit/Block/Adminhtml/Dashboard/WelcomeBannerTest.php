<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Block\Adminhtml\Dashboard\WelcomeBanner;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Welcome;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class WelcomeBannerTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Dashboard\WelcomeBanner
 */
class WelcomeBannerTest extends TestCase
{
    /** @var WelcomeBanner $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|Welcome $welcome */
    private $welcome;

    /** @var MockObject|Stores $stores */
    private $stores;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->welcome = $this->getMockBuilder(Welcome::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stores = $this->getMockBuilder(Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new WelcomeBanner(
            $this->context,
            $this->welcome,
            $this->stores
        );
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(WelcomeBanner::class, $this->object);
    }

    /**
     * Tests that getPureclarityStoresViewModel returns the right class
     */
    public function testGetPureclarityStoresViewModel()
    {
        self::assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }

    /**
     * Tests that getPureclarityWelcomeViewModel returns the right class
     */
    public function testGetPureclarityWelcomeViewModel()
    {
        self::assertInstanceOf(Welcome::class, $this->object->getPureclarityWelcomeViewModel());
    }
}
