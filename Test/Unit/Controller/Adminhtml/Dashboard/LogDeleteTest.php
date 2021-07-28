<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Dashboard\LogDelete;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Data\Form\FormKey\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Message\ManagerInterface;
use Pureclarity\Core\Model\Log\Delete;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class LogDeleteTest
 *
 * Tests the methods in \Pureclarity\Core\Controller\Adminhtml\Dashboard\LogDelete
 */
class LogDeleteTest extends TestCase
{
    /** @var LogDelete $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|Validator $formKeyValidator */
    private $formKeyValidator;

    /** @var MockObject|Http $request */
    private $request;

    /** @var MockObject|ManagerInterface $messageManager */
    private $messageManager;

    /** @var MockObject|RedirectFactory $resultRedirectFactory */
    private $resultRedirectFactory;

    /** @var MockObject|Redirect $redirect */
    private $redirect;

    /** @var MockObject|Delete $logDelete */
    private $logDelete;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->formKeyValidator = $this->createMock(Validator::class);
        $this->resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $this->context = $this->createMock(Context::class);
        $this->logDelete = $this->createMock(Delete::class);
        $this->redirect = $this->createMock(Redirect::class);

        $this->context->method('getRequest')
            ->willReturn($this->request);

        $this->context->method('getMessageManager')
            ->willReturn($this->messageManager);

        $this->context->method('getFormKeyValidator')
            ->willReturn($this->formKeyValidator);

        $this->context->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactory);

        $this->resultRedirectFactory->method('create')
            ->willReturn($this->redirect);

        $this->object = new LogDelete(
            $this->context,
            $this->logDelete
        );
    }

    /**
     * Sets up Request isPost response
     * @param bool $response
     */
    private function setupRequestIsPost(bool $response): void
    {
        $this->request->expects(self::once())
            ->method('isPost')
            ->willReturn($response);
    }

    /**
     * Sets up FormKeyValidator validate response
     * @param bool $response
     */
    private function setupFormKeyValidator(bool $response): void
    {
        $this->formKeyValidator->expects(self::once())
            ->method('validate')
            ->willReturn($response);
    }

    /**
     * Sets up Delete deleteLogs response
     * @param bool $response
     */
    private function setupDeleteLogs(bool $response): void
    {
        $this->logDelete->expects(self::once())
            ->method('deleteLogs')
            ->willReturn($response);
    }

    /**
     * Test class is instantiated correctly
     */
    public function testInstance(): void
    {
        $this->assertInstanceOf(LogDelete::class, $this->object);
    }

    /**
     * Test class is instantiated correctly
     */
    public function testAction(): void
    {
        $this->assertInstanceOf(Action::class, $this->object);
    }

    /**
     * Tests that execute handles isPost false correctly
     */
    public function testExecuteInvalidPost(): void
    {
        $this->setupRequestIsPost(false);

        $this->messageManager->expects(self::once())
            ->method('addErrorMessage')
            ->with('Invalid request, please reload the page and try again');

        $this->assertInstanceOf(Redirect::class, $this->object->execute());
    }

    /**
     * Tests that execute handles an invalid form key correctly
     */
    public function testExecuteInvalidFormKey(): void
    {
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(false);

        $this->messageManager->expects(self::once())
            ->method('addErrorMessage')
            ->with('Invalid form key, please reload the page and try again');

        $this->assertInstanceOf(Redirect::class, $this->object->execute());
    }

    /**
     * Tests that execute handles logs failing to delete
     */
    public function testExecuteDeleteFail(): void
    {
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);
        $this->setupDeleteLogs(false);

        $this->messageManager->expects(self::once())
            ->method('addErrorMessage')
            ->with('Logs failed to delete, please try again.');

        $this->assertInstanceOf(Redirect::class, $this->object->execute());
    }

    /**
     * Tests that execute handles logs failing to delete
     */
    public function testExecuteDeleteSuccess(): void
    {
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);
        $this->setupDeleteLogs(true);

        $this->messageManager->expects(self::once())
            ->method('addSuccessMessage')
            ->with('Logs deleted successfully');

        $this->assertInstanceOf(Redirect::class, $this->object->execute());
    }
}
