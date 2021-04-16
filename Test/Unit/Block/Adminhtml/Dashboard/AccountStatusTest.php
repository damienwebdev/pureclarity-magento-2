<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Block\Adminhtml\Dashboard\AccountStatus;
use Pureclarity\Core\Model\Dashboard;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class AccountStatusTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Dashboard\AccountStatus
 */
class AccountStatusTest extends TestCase
{
    /** @var AccountStatus $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|Dashboard $dashboard */
    private $dashboard;

    /** @var MockObject|Stores $stores */
    private $stores;

    /** @var MockObject|TimezoneInterface $localeDate */
    private $localeDate;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $date = $this->getMockBuilder(\DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDate = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDate->method('date')
            ->willReturn($date);

        $this->localeDate->method('formatDateTime')
            ->willReturn('System Date');

        $this->context->method('getLocaleDate')
            ->willReturn($this->localeDate);

        $this->dashboard = $this->getMockBuilder(Dashboard::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stores = $this->getMockBuilder(Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new AccountStatus(
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
        self::assertInstanceOf(AccountStatus::class, $this->object);
    }

    /**
     * Tests that getPureclarityStoresViewModel returns the right class
     */
    public function testGetPureclarityStoresViewModel()
    {
        self::assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }

    /**
     * Tests that getStatusClass returns an empty value when above 4 days of trial left
     */
    public function testGetStatusClassNone()
    {
        self::assertEquals('', $this->object->getStatusClass(17));
    }

    /**
     * Tests that getStatusClass returns the right value when less than 4 days of trial left
     */
    public function testGetStatusClassWarning()
    {
        self::assertEquals('pc-ft-warning', $this->object->getStatusClass(3));
    }

    /**
     * Tests that getStatusClass returns the right value when no days of trial left
     */
    public function testGetStatusClassError()
    {
        self::assertEquals('pc-ft-error', $this->object->getStatusClass(0));
    }

    /**
     * Tests that getAccountStatus returns the data returned by the dashboard class
     */
    public function testGetAccountStatus()
    {
        $this->dashboard->method('getAccountStatus')
            ->with(17)
            ->willReturn(['data']);

        self::assertEquals(['data'], $this->object->getAccountStatus(17));
    }

    /**
     * Tests that getEndDate returns a value from Magento's date formatter when called
     */
    public function testGetEndDate()
    {
        self::assertEquals('System Date', $this->object->getEndDate(10));
    }

    /**
     * Tests that getEndDate handles possible error from Magento's date formatter
     */
    public function testGetEndDateError()
    {
        $this->localeDate->method('formatDateTime')
            ->willThrowException(new \Exception('SomeError'));

        self::assertEquals('', $this->object->getEndDate(17));
    }
}
