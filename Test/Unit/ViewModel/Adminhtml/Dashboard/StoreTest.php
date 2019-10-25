<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml\Dashboard;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Helper\StoreData;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Store;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class StoreTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Store
 */
class StoreTest extends TestCase
{
    /** @var Store $object */
    private $object;

    /** @var MockObject|StoreData $storeData */
    private $storeData;

    protected function setUp()
    {
        $this->storeData = $this->getMockBuilder(StoreData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Store(
            $this->storeData
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Store::class, $this->object);
    }

    public function testGetStoreURL()
    {
        $this->storeData->expects($this->once())
            ->method('getStoreURL')
            ->with(1)
            ->willReturn('http://www.google.com/');

        $this->assertEquals('http://www.google.com/', $this->object->getStoreURL(1));
    }

    public function testGetStoreCurrency()
    {
        $this->storeData->expects($this->once())
            ->method('getStoreCurrency')
            ->with(1)
            ->willReturn('GBP');

        $this->assertEquals('GBP', $this->object->getStoreCurrency(1));
    }

    public function testGetStoreTimezone()
    {
        $this->storeData->expects($this->once())
            ->method('getStoreTimezone')
            ->with(1)
            ->willReturn('Europe/London');

        $this->assertEquals('Europe/London', $this->object->getStoreTimezone(1));
    }
}
