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
use Magento\Framework\View\Result\Page;
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

    /** @var MockObject|Page $storeManager */
    private $storeManager;

    /** @var MockObject|Page $coreConfig */
    private $coreConfig;

    /** @var MockObject|Http $request */
    private $request;

    protected function setUp()
    {
        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->method('getRequest')
            ->willReturn($this->request);

        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPage = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['setActiveMenu'])
            ->getMock();

        $this->resultPageFactory->method('create')
            ->willReturn($this->resultPage);

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Index(
            $this->context,
            $this->resultPageFactory,
            $this->storeManager,
            $this->coreConfig
        );
    }

    private function setupGetStores()
    {
        $store1 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store1->expects($this->any())
            ->method('getId')
            ->willReturn('1');

        $store2 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store2->method('getId')
            ->willReturn('17');

        $this->storeManager->expects(self::once())
            ->method('getStores')
            ->willReturn([$store1, $store2]);
    }

    private function setupDefaultStore()
    {
        $store1 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store1->method('getId')
            ->willReturn('42');

        $this->storeManager->expects(self::once())
            ->method('getDefaultStoreView')
            ->willReturn($store1);
    }

    public function testInstance()
    {
        self::assertInstanceOf(Index::class, $this->object);
    }

    public function testAction()
    {
        self::assertInstanceOf(Action::class, $this->object);
    }

    public function testExecute()
    {
        $this->resultPage->expects(self::once())
            ->method('setActiveMenu')
            ->with('Magento_Backend::content');

        $result = $this->object->execute();
        self::assertInstanceOf(Page::class, $result);
    }

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
