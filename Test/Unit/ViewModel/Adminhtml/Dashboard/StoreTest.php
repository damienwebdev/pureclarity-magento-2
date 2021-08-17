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

    protected function setUp(): void
    {
        $this->storeData = $this->createMock(StoreData::class);

        $this->object = new Store(
            $this->storeData
        );
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        $this->assertInstanceOf(Store::class, $this->object);
    }

    /**
     * Tests getStoreURL returns the value provided by Magento
     */
    public function testGetStoreURL()
    {
        $this->storeData->expects($this->once())
            ->method('getStoreURL')
            ->with(1)
            ->willReturn('http://www.google.com/');

        $this->assertEquals('http://www.google.com/', $this->object->getStoreURL(1));
    }

    /**
     * Tests getStoreCurrency returns the value provided by Magento
     */
    public function testGetStoreCurrency()
    {
        $this->storeData->expects($this->once())
            ->method('getStoreCurrency')
            ->with(1)
            ->willReturn('GBP');

        $this->assertEquals('GBP', $this->object->getStoreCurrency(1));
    }

    /**
     * Tests getStoreTimezone returns the value provided by Magento
     */
    public function testGetStoreTimezone()
    {
        $this->storeData->expects($this->once())
            ->method('getStoreTimezone')
            ->with(1)
            ->willReturn('Europe/London');

        $this->assertEquals('Europe/London', $this->object->getStoreTimezone(1));
    }
}
