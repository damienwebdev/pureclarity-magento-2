<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\State;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;

/**
 * Class DataTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class StoresTest extends TestCase
{
    /** @var Stores $object */
    private $object;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var StoreInterface $store */
    private $store;

    protected function setUp()
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Stores(
            $this->storeManager,
            $this->stateRepository
        );
    }

    /**
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

    public function testInterface()
    {
        $this->assertInstanceOf(ArgumentInterface::class, $this->object);
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

    public function testGetPureClarityDefaultStoreWithDefault()
    {
        $stateObject = $this->getStateMock('2');
        $this->stateRepository->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($stateObject);

        $storeId = $this->object->getPureClarityDefaultStore();
        $this->assertEquals(2, $storeId);

        // call again, to ensure in-object caching works
        $storeId = $this->object->getPureClarityDefaultStore();
        $this->assertEquals(2, $storeId);
    }

    public function testGetPureClarityDefaultStoreNoDefault()
    {
        $stateObject = $this->getStateMock();
        $this->stateRepository->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($stateObject);

        $this->storeManager->expects($this->once())
            ->method('getDefaultStoreView')
            ->willReturn($this->getStoreMock(7, 'Store 7'));

        $storeId = $this->object->getPureClarityDefaultStore();
        $this->assertEquals(7, $storeId);

        $storeId = $this->object->getPureClarityDefaultStore();
        $this->assertEquals(7, $storeId);
    }

    public function testGetStoreList()
    {
        $this->storeManager->expects($this->once())
            ->method('getStores')
            ->willReturn([
                $this->getStoreMock(2, 'Store 2'),
                $this->getStoreMock(7, 'Store 7')
            ]);

        $expected = [
            2 => 'Store 2',
            7 => 'Store 7'
        ];

        $this->assertEquals($expected, $this->object->getStoreList());
    }
}
