<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Helper\StoreData;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class StoreDataTest
 *
 * Tests the methods in \Pureclarity\Core\Helper\StoreData
 */
class StoreDataTest extends TestCase
{
    /** @var StoreData $object */
    private $object;

    /** @var MockObject|ScopeConfigInterface $scopeConfig */
    private $scopeConfig;

    /** @var MockObject|StoreManagerInterface $writer */
    private $storeManager;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);

        $this->object = new StoreData(
            $this->storeManager,
            $this->scopeConfig
        );
    }

    public function testStoreDataInstance()
    {
        $this->assertInstanceOf(StoreData::class, $this->object);
    }

    public function testGetStoreURLsecure()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(StoreData::CONFIG_PATH_SECURE_IN_FRONTEND, ScopeInterface::SCOPE_STORE, 17)
            ->willReturn(true);

        $this->scopeConfig->expects($this->at(1))
            ->method('getValue')
            ->with(StoreData::CONFIG_PATH_SECURE_BASE_URL, ScopeInterface::SCOPE_STORE, 17)
            ->willReturn('http://www.google.com');

        $this->assertEquals('http://www.google.com', $this->object->getStoreURL(17));
    }

    public function testGetStoreURLUnsecure()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(StoreData::CONFIG_PATH_SECURE_IN_FRONTEND, ScopeInterface::SCOPE_STORE, 17)
            ->willReturn(false);

        $this->scopeConfig->expects($this->at(1))
            ->method('getValue')
            ->with(StoreData::CONFIG_PATH_UNSECURE_BASE_URL, ScopeInterface::SCOPE_STORE, 17)
            ->willReturn('http://www.google.com');

        $this->assertEquals('http://www.google.com', $this->object->getStoreURL(17));
    }

    public function testGetStoreCurrency()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(StoreData::CONFIG_PATH_DEFAULT_CURRENCY, ScopeInterface::SCOPE_STORE, 17)
            ->willReturn('GBP');

        $this->assertEquals('GBP', $this->object->getStoreCurrency(17));
    }

    public function testGetStoreTimezone()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(StoreData::CONFIG_PATH_TIMEZONE, ScopeInterface::SCOPE_STORE, 17)
            ->willReturn('GBP');

        $this->assertEquals('GBP', $this->object->getStoreTimezone(17));
    }
}
