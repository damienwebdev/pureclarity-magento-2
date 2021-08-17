<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Framework\App\Request\Http;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Dashboard\Index;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class IndexTest
 *
 * Tests the methods in \Pureclarity\Core\Controller\Adminhtml\Dashboard\Index
 */
class IndexTest extends TestCase
{
    /** @var Index $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|PageFactory $resultPageFactory */
    private $resultPageFactory;

    /** @var MockObject|Page $resultPage */
    private $resultPage;

    /** @var MockObject|StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var MockObject|CoreConfig $coreConfig */
    private $coreConfig;

    /** @var MockObject|Http $request */
    private $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->context = $this->createMock(Context::class);

        $this->context->method('getRequest')
            ->willReturn($this->request);

        $this->resultPageFactory = $this->createMock(PageFactory::class);

        $this->resultPage = $this->createPartialMock(
            Page::class,
            ['setActiveMenu']
        );

        $this->resultPageFactory->method('create')
            ->willReturn($this->resultPage);

        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->coreConfig = $this->createMock(CoreConfig::class);

        $this->object = new Index(
            $this->context,
            $this->resultPageFactory,
            $this->storeManager,
            $this->coreConfig
        );
    }

    /**
     * Sets up StoreManagerInterface getStores so it returns 2 stores
     */
    private function setupGetStores()
    {
        $store1 = $this->createMock(StoreInterface::class);

        $store1->expects($this->any())
            ->method('getId')
            ->willReturn('1');

        $store2 = $this->createMock(StoreInterface::class);

        $store2->method('getId')
            ->willReturn('17');

        $this->storeManager->expects(self::once())
            ->method('getStores')
            ->willReturn([$store1, $store2]);
    }

    /**
     * Sets up StoreManagerInterface getDefaultStoreView so it returns a store
     */
    private function setupDefaultStore()
    {
        $store1 = $this->createMock(StoreInterface::class);

        $store1->method('getId')
            ->willReturn('42');

        $this->storeManager->expects(self::once())
            ->method('getDefaultStoreView')
            ->willReturn($store1);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(Index::class, $this->object);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testAction()
    {
        self::assertInstanceOf(Action::class, $this->object);
    }

    /**
     * Tests the execute function in single store mode, making sure menu is set correctly
     */
    public function testExecute()
    {
        $this->resultPage->expects(self::once())
            ->method('setActiveMenu')
            ->with('Magento_Backend::content');

        $result = $this->object->execute();
        self::assertInstanceOf(Page::class, $result);
    }

    /**
     * Tests the execute function in multi store mode, with a store selected
     */
    public function testExecuteMultiStoreSelected()
    {
        $this->resultPage->expects(self::once())
            ->method('setActiveMenu')
            ->with('Magento_Backend::content');

        $this->storeManager->method('hasSingleStore')
            ->willReturn(false);

        $this->request->method('getParam')
            ->with('store')
            ->willReturn(1);

        $this->request->expects(self::once())
            ->method('setParams')
            ->with(['store' => 1])
            ->willReturn(1);

        $result = $this->object->execute();
        self::assertInstanceOf(Page::class, $result);
    }

    /**
     * Tests the execute function in multi store mode, with no store selected and one store being configured
     * meaning the configured store should be selected
     */
    public function testExecuteMultiStoreNoneSelectedOneConfigured()
    {
        $this->resultPage->expects(self::once())
            ->method('setActiveMenu')
            ->with('Magento_Backend::content');

        $this->setupGetStores();

        $this->storeManager->method('hasSingleStore')
            ->willReturn(false);

        $this->request->method('getParam')
            ->with('store')
            ->willReturn(null);

        $this->request->expects(self::once())
            ->method('setParams')
            ->with(['store' => 17]);

        $this->coreConfig->expects(self::at(2))
            ->method('getAccessKey')
            ->willReturn('AccessKey');

        $this->coreConfig->expects(self::at(3))
            ->method('getSecretKey')
            ->willReturn('SecretKey');

        $result = $this->object->execute();
        self::assertInstanceOf(Page::class, $result);
    }

    /**
     * Tests the execute function in multi store mode, with no store selected and no stores being configured
     * meaning the default store should be selected
     */
    public function testExecuteMultiStoreDefault()
    {
        $this->resultPage->expects(self::once())
            ->method('setActiveMenu')
            ->with('Magento_Backend::content');

        $this->setupGetStores();
        $this->setupDefaultStore();

        $this->storeManager->method('hasSingleStore')
            ->willReturn(false);

        $this->request->method('getParam')
            ->with('store')
            ->willReturn(null);

        $this->request->expects(self::once())
            ->method('setParams')
            ->with(['store' => 42]);

        $result = $this->object->execute();
        self::assertInstanceOf(Page::class, $result);
    }

    /**
     * Tests the execute function in multi store mode, with no store selected, no stores being configured
     * and no default store so 0 should be selected
     */
    public function testExecuteMultiStoreNone()
    {
        $this->resultPage->expects(self::once())
            ->method('setActiveMenu')
            ->with('Magento_Backend::content');

        $this->setupGetStores();

        $this->storeManager->method('hasSingleStore')
            ->willReturn(false);

        $this->request->method('getParam')
            ->with('store')
            ->willReturn(null);

        $this->request->expects(self::once())
            ->method('setParams')
            ->with(['store' => 0]);

        $result = $this->object->execute();
        self::assertInstanceOf(Page::class, $result);
    }
}
