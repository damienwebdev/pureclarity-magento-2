<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Dashboard\Signup;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Pureclarity\Core\Model\Signup\Request;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SignupTest
 *
 * Tests the methods in \Pureclarity\Core\Controller\Adminhtml\Dashboard\Signup
 */
class SignupTest extends TestCase
{
    /** @var array $defaultParams */
    private $defaultParams = [
        'param1' => 'param1',
        'param2' => 'param1',
    ];

    /** @var Signup $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var MockObject|Json $json */
    private $json;

    /** @var MockObject|Request $signupRequest */
    private $signupRequest;

    /** @var MockObject|Validator $formKeyValidator */
    private $formKeyValidator;

    /** @var MockObject|Http $request */
    private $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->context = $this->createMock(Context::class);

        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->formKeyValidator = $this->createMock(Validator::class);

        $this->context->expects($this->any())
            ->method('getFormKeyValidator')
            ->willReturn($this->formKeyValidator);

        $this->signupRequest = $this->createMock(Request::class);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->json = $this->createMock(Json::class);

        $this->jsonFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->json);

        $this->object = new Signup(
            $this->context,
            $this->signupRequest,
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
        $this->assertInstanceOf(Signup::class, $this->object);
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

    public function testExecuteInvalidSignupErrors()
    {
        $this->setupRequestGetParams();
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);

        $this->signupRequest->expects($this->once())
            ->method('sendRequest')
            ->with($this->defaultParams)
            ->willReturn([
                'error' => 'Some Error'
            ]);

        $this->json->expects($this->once())
            ->method('setData')
            ->with([
                'error' => 'Some Error',
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

        $this->signupRequest->expects($this->once())
            ->method('sendRequest')
            ->with($this->defaultParams)
            ->willReturn([
                'error' => [],
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
