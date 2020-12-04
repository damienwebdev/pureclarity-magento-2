<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\State;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use Psr\Log\LoggerInterface;

/**
 * Class StoresTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Stores
 */
class StoresTest extends TestCase
{
    /** @var Stores $object */
    private $object;

    /** @var MockObject|StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var MockObject|StateRepositoryInterface $stateRepository */
    private $stateRepository;

    protected function setUp()
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Stores(
            $this->storeManager,
            $request,
            $logger
        );
    }

    /**
     * @param string $value
     * @return MockObject
     */
    private function getStateMock($value = null)
    {
        $state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $state->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $state->expects($this->any())
            ->method('getStoreId')
            ->willReturn('0');

        $state->expects($this->any())
            ->method('getName')
            ->willReturn('default_store');

        $state->expects($this->any())
            ->method('getValue')
            ->willReturn($value);

        return $state;
    }

    /**
     * @param integer $id
     * @param string $name
     * @return MockObject
     */
    private function getStoreMock($id, $name)
    {
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $store->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $store;
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Stores::class, $this->object);
    }

    public function testGetMagentoDefaultStore()
    {
        $this->storeManager->expects($this->once())
            ->method('getDefaultStoreView')
            ->willReturn($this->getStoreMock(3, 'Store 1'));

        $store = $this->object->getMagentoDefaultStore();

        $this->assertEquals(3, $store->getId());

        // call again, to ensure in-object caching works
        $store = $this->object->getMagentoDefaultStore();

        $this->assertEquals(3, $store->getId());
    }

    public function testHasMultipleStoresFalse()
    {
        $this->storeManager->expects($this->once())
            ->method('hasSingleStore')
            ->willReturn(true);

        $this->assertEquals(false, $this->object->hasMultipleStores());
    }

    public function testHasMultipleStoresTrue()
    {
        $this->storeManager->expects($this->once())
            ->method('hasSingleStore')
            ->willReturn(false);

        $this->assertEquals(true, $this->object->hasMultipleStores());
    }
}
