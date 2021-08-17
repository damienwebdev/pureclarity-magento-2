<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Block\Adminhtml\Dashboard\NextSteps;
use Pureclarity\Core\Model\Dashboard;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class NextStepsTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Dashboard\NextSteps
 */
class NextStepsTest extends TestCase
{
    /** @var NextSteps $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|Dashboard $dashboard */
    private $dashboard;

    /** @var MockObject|Stores $stores */
    private $stores;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->dashboard = $this->createMock(Dashboard::class);
        $this->stores = $this->createMock(Stores::class);

        $this->object = new NextSteps(
            $this->context,
            $this->dashboard,
            $this->stores
        );
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(NextSteps::class, $this->object);
    }

    /**
     * Tests that getPureclarityStoresViewModel returns the right class
     */
    public function testGetPureclarityStoresViewModel()
    {
        self::assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }

    /**
     * Tests that getNextSteps returns the data returned by the dashboard class
     */
    public function testGetNextSteps()
    {
        $this->dashboard->method('getNextSteps')
            ->with(17)
            ->willReturn(['data']);
        self::assertEquals(['data'], $this->object->getNextSteps(17));
    }

    /**
     * Tests that testGetAdminUrl returns an Admin URL with the provided link appended
     */
    public function testGetAdminUrl()
    {
        self::assertEquals(
            'https://admin.pureclarity.com/some/url/',
            $this->object->getAdminUrl('some/url/')
        );
    }
}
