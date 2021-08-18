<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Dashboard\LogDownload;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Data\Form\FormKey\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class LogDownloadTest
 *
 * Tests the methods in \Pureclarity\Core\Controller\Adminhtml\Dashboard\LogDownload
 */
class LogDownloadTest extends TestCase
{
    /** @var LogDownload $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|Validator $formKeyValidator */
    private $formKeyValidator;

    /** @var MockObject|Http $request */
    private $request;

    /** @var MockObject|ManagerInterface $messageManager */
    private $messageManager;

    /** @var FileFactory $fileFactory */
    private $fileFactory;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->formKeyValidator = $this->createMock(Validator::class);
        $this->fileFactory = $this->createMock(FileFactory::class);
        $this->context = $this->createMock(Context::class);
        $this->resultRedirectFactory = $this->createMock(RedirectFactory::class);
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

        $this->object = new LogDownload(
            $this->context,
            $this->fileFactory
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
     * Test class is instantiated correctly
     */
    public function testInstance(): void
    {
        $this->assertInstanceOf(LogDownload::class, $this->object);
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
     * Tests that execute handles logs failing to download
     */
    public function testExecuteDownloadFail(): void
    {
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);

        $this->fileFactory->method('create')
            ->willThrowException(new \Exception('An error'));

        $this->messageManager->expects(self::once())
            ->method('addErrorMessage')
            ->with('Log failed to download, please try again');

        $this->assertInstanceOf(Redirect::class, $this->object->execute());
    }

    /**
     * Tests that execute handles logs downloading correctly
     */
    public function testExecuteResetSuccess(): void
    {
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);

        $response = $this->createMock(ResponseInterface::class);
        $this->fileFactory->method('create')
            ->with(
                'pureclarity.log',
                [
                    'type' => 'filename',
                    'value' => 'log/pureclarity.log'
                ],
                DirectoryList::VAR_DIR
            )->willReturn($response);

        $this->assertInstanceOf(ResponseInterface::class, $this->object->execute());
    }
}
