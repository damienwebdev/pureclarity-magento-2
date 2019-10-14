<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Dashboard\Configure;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Pureclarity\Core\Model\Signup\Process;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ConfigureTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class ConfigureTest extends TestCase
{
    /** @var array $defaultParams */
    private $defaultParams = [
        'param1' => 'param1',
        'param2' => 'param1',
    ];

    /** @var Configure $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var MockObject|Json $json */
    private $json;

    /** @var MockObject|Process $requestProcess */
    private $requestProcess;

    /** @var MockObject|Validator $formKeyValidator */
    private $formKeyValidator;

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

        $this->formKeyValidator = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->any())
            ->method('getFormKeyValidator')
            ->willReturn($this->formKeyValidator);

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

        $this->object = new Configure(
            $this->context,
            $this->requestProcess,
            $this->jsonFactory
        );
    }

    private function setupRequestGetParams()
    {
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($this->defaultParams);
    }

    private function setupRequestIsPost($response)
    {
        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn($response);
    }

    private function setupFormKeyValidator($response)
    {
        $this->formKeyValidator->expects($this->once())
            ->method('validate')
            ->willReturn($response);
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Configure::class, $this->object);
    }

    public function testAction()
    {
        $this->assertInstanceOf(Action::class, $this->object);
    }

    public function testExecuteInvalidPost()
    {
        $this->setupRequestIsPost(false);

        $this->json->expects($this->once())
            ->method('setData')
            ->with([
                'error' => 'Invalid request, please reload the page and try again',
                'success' => false
            ]);

        $result = $this->object->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteInvalidFormKey()
    {
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(false);

        $this->json->expects($this->once())
            ->method('setData')
            ->with([
                'error' => 'Invalid form key, please reload the page and try again',
                'success' => false
            ]);

        $result = $this->object->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteInvalidConfigureErrors()
    {
        $this->setupRequestGetParams();
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);

        $this->requestProcess->expects($this->once())
            ->method('processManualConfigure')
            ->with($this->defaultParams)
            ->willReturn([
                'errors' => ['error1', 'error2']
            ]);

        $this->json->expects($this->once())
            ->method('setData')
            ->with([
                'error' => 'error1,error2',
                'success' => false
            ]);

        $result = $this->object->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteSuccess()
    {
        $this->setupRequestIsPost(true);
        $this->setupRequestGetParams();
        $this->setupFormKeyValidator(true);

        $this->requestProcess->expects($this->once())
            ->method('processManualConfigure')
            ->with($this->defaultParams)
            ->willReturn([
                'errors' => [],
                'success' => true
            ]);

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
