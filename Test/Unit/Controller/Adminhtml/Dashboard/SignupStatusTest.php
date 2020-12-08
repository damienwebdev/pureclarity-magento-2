<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Dashboard\SignupStatus;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Pureclarity\Core\Model\Signup\Process;
use Pureclarity\Core\Model\Signup\Status as RequestStatus;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SignupStatusTest
 *
 * Tests the methods in \Pureclarity\Core\Controller\Adminhtml\Dashboard\SignupStatus
 */
class SignupStatusTest extends TestCase
{
    /** @var array $defaultParams */
    private $defaultParams = [
        'param1' => 'param1',
        'param2' => 'param1',
    ];

    /** @var SignupStatus $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var MockObject|Json $json */
    private $json;

    /** @var MockObject|RequestStatus $requestStatus */
    private $requestStatus;

    /** @var MockObject|Process $requestProcess */
    private $requestProcess;

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

        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->requestStatus = $this->getMockBuilder(RequestStatus::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestProcess = $this->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->json);

        $this->object = new SignupStatus(
            $this->context,
            $this->requestStatus,
            $this->requestProcess,
            $this->jsonFactory
        );
    }

    /**
     * Sets up Http isGet with the provided flag
     * @param bool $response
     */
    private function setupRequestIsGet($response)
    {
        $this->request->expects($this->once())
            ->method('isGet')
            ->willReturn($response);
    }

    /**
     * Sets up Http getParams for 'store' param, with the provided store id
     * @param integer $store
     */
    private function setupRequestGetParamStore($store)
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn($store);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        $this->assertInstanceOf(SignupStatus::class, $this->object);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testAction()
    {
        $this->assertInstanceOf(Action::class, $this->object);
    }

    /**
     * Tests how execute handles a non-GET call
     */
    public function testExecuteInvalidGet()
    {
        $this->setupRequestIsGet(false);

        $this->json->expects($this->once())
            ->method('setData')
            ->with([
                'error' => 'Invalid request, please reload the page and try again',
                'success' => false
            ]);

        $result = $this->object->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    /**
     * Tests how execute handles a missing store ID
     */
    public function testExecuteInvalidNoStore()
    {
        $this->setupRequestIsGet(true);

        $this->json->expects($this->once())
            ->method('setData')
            ->with([
                'error' => 'Invalid request, please reload the page and try again',
                'success' => false
            ]);

        $result = $this->object->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    /**
     * Tests how execute handles an error returned by the checkStatus call
     */
    public function testExecuteInvalidSignupStatusErrors()
    {
        $this->setupRequestIsGet(true);
        $this->setupRequestGetParamStore(1);

        $this->requestStatus->expects($this->once())
            ->method('checkStatus')
            ->willReturn([
                'error' => 'error1',
                'complete' => false,
            ]);

        $this->json->expects($this->once())
            ->method('setData')
            ->with([
                'error' => 'error1',
                'success' => false
            ]);

        $result = $this->object->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    /**
     * Tests how execute handles a successful checkStatus call
     */
    public function testExecuteSuccess()
    {
        $this->setupRequestIsGet(true);
        $this->setupRequestGetParamStore(1);

        $this->requestStatus->expects($this->once())
            ->method('checkStatus')
            ->willReturn([
                'error' => '',
                'complete' => true,
                'response' => $this->defaultParams,
            ]);

        $this->requestProcess->expects($this->once())
            ->method('process')
            ->with($this->defaultParams);

        $this->json->expects($this->once())
            ->method('setData')
            ->with([
                'error' => '',
                'success' => true
            ]);

        $result = $this->object->execute();
        $this->assertInstanceOf(Json::class, $result);
    }
}
