<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

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
    protected $resultPageFactory;

    /** @var MockObject|Page $resultPage */
    protected $resultPage;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPage = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['setActiveMenu'])
            ->getMock();

        $this->resultPageFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->resultPage);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Index(
            $this->context,
            $this->resultPageFactory,
            $storeManager,
            $coreConfig
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Index::class, $this->object);
    }

    public function testAction()
    {
        $this->assertInstanceOf(Action::class, $this->object);
    }

    public function testExecute()
    {
        $this->resultPage->expects($this->once())
            ->method('setActiveMenu')
            ->with('Magento_Backend::content');

        $result = $this->object->execute();
        $this->assertInstanceOf(Page::class, $result);
    }
}
