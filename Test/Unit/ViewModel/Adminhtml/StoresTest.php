<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

    /** @var MockObject|RequestInterface $request */
    private $request;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Stores(
            $this->storeManager,
            $this->request,
            $this->logger
        );
    }

    /**
     * Generates a StoreInterface Mock
     * @param integer $id
     * @param string $name
     * @return MockObject
     */
    private function getStoreMock($id, $name)
    {
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        $store->expects(self::any())
            ->method('getName')
            ->willReturn($name);

        return $store;
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(Stores::class, $this->object);
    }

    /**
     * Tests getStoreId returns the store ID based on the store ID in request when present.
     */
    public function testGetStoreIdWithRequest()
    {
        $loadedStore = $this->getStoreMock(17, 'Store 1');

        $this->request->method('getParam')
            ->with('store')
            ->willReturn(17);

        $this->storeManager->expects(self::once())
            ->method('getStore')
            ->with(17)
            ->willReturn($loadedStore);

        $store = $this->object->getStoreId();

        self::assertEquals(17, $store);

        // call again, to check caching works
        $store = $this->object->getStoreId();

        self::assertEquals(17, $store);
    }

    /**
     * Tests that getStoreId returns 0 as the store id when no store is loaded
     */
    public function testGetStoreIdZero()
    {
        $this->request->method('getParam')
            ->with('store')
            ->willReturn(null);

        $this->storeManager->expects(self::once())
            ->method('getDefaultStoreView')
            ->willReturn(null);

        $store = $this->object->getStoreId();

        self::assertEquals(0, $store);
    }

    /**
     * Tests getStore uses the store ID in request when present.
     */
    public function testGetStoreWithRequest()
    {
        $loadedStore = $this->getStoreMock(3, 'Store 1');

        $this->request->method('getParam')
            ->with('store')
            ->willReturn(3);

        $this->storeManager->expects(self::once())
            ->method('getStore')
            ->with(3)
            ->willReturn($loadedStore);

        $store = $this->object->getStore();

        self::assertEquals($loadedStore, $store);

        // call again, to check caching works
        $store = $this->object->getStore();

        self::assertEquals($loadedStore, $store);
    }

    /**
     * Tests getStore handles an Exception on loading a store
     */
    public function testGetStoreWithRequestException()
    {
        $this->request->method('getParam')
            ->with('store')
            ->willReturn(17);

        $this->storeManager->expects(self::once())
            ->method('getStore')
            ->with(17)
            ->willThrowException(new NoSuchEntityException(new Phrase('An error')));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Admin Dashboard could not load selected store - An error');

        $this->storeManager->expects(self::once())
            ->method('getDefaultStoreView')
            ->willReturn($this->getStoreMock(3, 'Store 1'));

        $store = $this->object->getStore();

        self::assertEquals(3, $store->getId());
    }

    /**
     * Tests getStore handles loading the default store when no request value present
     */
    public function testGetStoreWithNoRequest()
    {
        $this->request->method('getParam')
            ->with('store')
            ->willReturn(null);

        $this->storeManager->expects(self::never())
            ->method('getStore');

        $this->storeManager->expects(self::once())
            ->method('getDefaultStoreView')
            ->willReturn($this->getStoreMock(3, 'Store 1'));

        $store = $this->object->getStore();

        self::assertEquals(3, $store->getId());

        // call again, to ensure in-object caching works
        $store = $this->object->getMagentoDefaultStore();

        self::assertEquals(3, $store->getId());
    }

    /**
     * Tests getMagentoDefaultStore handles loading the default store
     */
    public function testGetMagentoDefaultStore()
    {
        $this->storeManager->expects(self::once())
            ->method('getDefaultStoreView')
            ->willReturn($this->getStoreMock(3, 'Store 1'));

        $store = $this->object->getMagentoDefaultStore();

        self::assertEquals(3, $store->getId());

        // call again, to ensure in-object caching works
        $store = $this->object->getMagentoDefaultStore();

        self::assertEquals(3, $store->getId());
    }

    /**
     * Tests hasMultipleStores returns the correct flag from Magento
     */
    public function testHasMultipleStoresFalse()
    {
        $this->storeManager->expects(self::once())
            ->method('hasSingleStore')
            ->willReturn(true);

        self::assertEquals(false, $this->object->hasMultipleStores());
    }

    /**
     * Tests hasMultipleStores returns the correct flag from Magento
     */
    public function testHasMultipleStoresTrue()
    {
        $this->storeManager->expects(self::once())
            ->method('hasSingleStore')
            ->willReturn(false);

        self::assertEquals(true, $this->object->hasMultipleStores());
    }
}
