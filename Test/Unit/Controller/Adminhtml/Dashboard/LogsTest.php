<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Dashboard\Logs;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\App\Action;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class LogsTest
 *
 * Tests the methods in \Pureclarity\Core\Controller\Adminhtml\Dashboard\Logs
 */
class LogsTest extends TestCase
{
    /** @var Logs $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|PageFactory $resultPageFactory */
    private $resultPageFactory;

    /** @var MockObject|Page $resultPage */
    private $resultPage;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->resultPage = $this->createPartialMock(Page::class, ['setActiveMenu']);

        $this->resultPageFactory->method('create')
            ->willReturn($this->resultPage);

        $this->object = new Logs(
            $this->context,
            $this->resultPageFactory
        );
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Logs::class, $this->object);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testAction(): void
    {
        self::assertInstanceOf(Action::class, $this->object);
    }

    /**
     * Tests the execute function making sure menu is set correctly
     */
    public function testExecute(): void
    {
        $this->resultPage->expects(self::once())
            ->method('setActiveMenu')
            ->with('Magento_Backend::content');

        $result = $this->object->execute();
        self::assertInstanceOf(Page::class, $result);
    }
}
