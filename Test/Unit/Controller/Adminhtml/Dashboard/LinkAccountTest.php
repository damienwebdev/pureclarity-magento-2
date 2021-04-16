<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Dashboard\LinkAccount;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Pureclarity\Core\Model\Signup\Process;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\Account\Validate;
use Pureclarity\Core\Model\Signup\AddStore;

/**
 * Class LinkAccountTest
 *
 * Tests the methods in \Pureclarity\Core\Controller\Adminhtml\Dashboard\LinkAccount
 */
class LinkAccountTest extends TestCase
{
    /** @var array $defaultParams */
    private $defaultParams = [
        'type' => 'link',
        'param1' => 'param1',
        'param2' => 'param1',
    ];

    /** @var LinkAccount $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var MockObject|Validate $validate */
    private $validate;

    /** @var MockObject|AddStore $addStore */
    private $addStore;

    /** @var MockObject|Json $json */
    private $json;

    /** @var MockObject|Process $requestProcess */
    private $requestProcess;

    /** @var MockObject|Validator $formKeyValidator */
    private $formKeyValidator;

    /** @var MockObject|Http $request */
    private $request;

    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->method('getRequest')
            ->willReturn($this->request);

        $this->formKeyValidator = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->method('getFormKeyValidator')
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

        $this->jsonFactory->method('create')
            ->willReturn($this->json);

        $this->validate = $this->getMockBuilder(Validate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addStore = $this->getMockBuilder(AddStore::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new LinkAccount(
            $this->context,
            $this->requestProcess,
            $this->jsonFactory,
            $this->validate,
            $this->addStore
        );
    }

    /**
     * Sets up Http getParams with either the default params or provided ones
     * @param array $params
     */
    private function setupRequestGetParams($params = [])
    {
        $this->request->expects(self::once())
            ->method('getParams')
            ->willReturn(empty($params) ? $this->defaultParams : $params);
    }

    /**
     * Sets up Http isPost with the provided flag
     * @param bool $response
     */
    private function setupRequestIsPost($response)
    {
        $this->request->expects(self::once())
            ->method('isPost')
            ->willReturn($response);
    }

    /**
     * Sets up Validator validate with the provided flag
     * @param bool $response
     */
    private function setupFormKeyValidator($response)
    {
        $this->formKeyValidator->expects(self::once())
            ->method('validate')
            ->willReturn($response);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(LinkAccount::class, $this->object);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testAction()
    {
        self::assertInstanceOf(Action::class, $this->object);
    }

    /**
     * Tests execute response to a non-POST call
     */
    public function testExecuteInvalidPost()
    {
        $this->setupRequestIsPost(false);

        $this->json->expects(self::once())
            ->method('setData')
            ->with([
                'error' => 'Invalid request, please reload the page and try again',
                'success' => false
            ]);

        $result = $this->object->execute();
        self::assertInstanceOf(Json::class, $result);
    }

    /**
     * Tests execute response to an invalid form key
     */
    public function testExecuteInvalidFormKey()
    {
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(false);

        $this->json->expects(self::once())
            ->method('setData')
            ->with([
                'error' => 'Invalid form key, please reload the page and try again',
                'success' => false
            ]);

        $result = $this->object->execute();
        self::assertInstanceOf(Json::class, $result);
    }

    /**
     * Tests that execute handles an error response from the validate account call
     */
    public function testExecuteLinkError()
    {
        $this->setupRequestGetParams();
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);

        $this->validate->expects(self::once())
            ->method('sendRequest')
            ->with($this->defaultParams)
            ->willReturn([
                'error' => 'An Error'
            ]);

        $this->requestProcess->expects(self::never())
            ->method('processManualConfigure');

        $this->json->expects(self::once())
            ->method('setData')
            ->with([
                'error' => 'An Error',
                'success' => false
            ]);

        $result = $this->object->execute();
        self::assertInstanceOf(Json::class, $result);
    }

    /**
     * Tests that execute handles an error response from the processManualConfigure call
     */
    public function testExecuteLinkInvalidConfigureErrors()
    {
        $this->setupRequestGetParams();
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);

        $this->requestProcess->expects(self::once())
            ->method('processManualConfigure')
            ->with($this->defaultParams)
            ->willReturn([
                'errors' => ['error1', 'error2']
            ]);

        $this->json->expects(self::once())
            ->method('setData')
            ->with([
                'error' => 'error1,error2',
                'success' => false
            ]);

        $result = $this->object->execute();
        self::assertInstanceOf(Json::class, $result);
    }

    /**
     * Tests that execute handles a link existing account request correctly
     */
    public function testExecuteLinkAccountSuccess()
    {
        $this->setupRequestIsPost(true);
        $this->setupRequestGetParams();
        $this->setupFormKeyValidator(true);

        $this->requestProcess->expects(self::once())
            ->method('processManualConfigure')
            ->with($this->defaultParams)
            ->willReturn([
                'errors' => [],
                'success' => true
            ]);

        $this->json->expects(self::once())
            ->method('setData')
            ->with([
                'error' => '',
                'success' => true
            ]);

        $result = $this->object->execute();
        self::assertInstanceOf(Json::class, $result);
    }

    /**
     * Tests that execute handles an add store request error
     */
    public function testExecuteAddStoreError()
    {
        $params = $this->defaultParams;
        $params['type'] = 'add';

        $this->setupRequestGetParams($params);
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);

        $this->addStore->expects(self::once())
            ->method('sendRequest')
            ->with($params)
            ->willReturn([
                'error' => 'An Add Store Error'
            ]);

        $this->requestProcess->expects(self::never())
            ->method('processManualConfigure');

        $this->json->expects(self::once())
            ->method('setData')
            ->with([
                'error' => 'An Add Store Error',
                'success' => false
            ]);

        $result = $this->object->execute();
        self::assertInstanceOf(Json::class, $result);
    }

    /**
     * Tests that execute handles an add store success
     */
    public function testExecuteAddStoreSuccess()
    {
        $params = $this->defaultParams;
        $params['type'] = 'add';

        $this->setupRequestGetParams($params);
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);

        $this->addStore->expects(self::once())
            ->method('sendRequest')
            ->with($params)
            ->willReturn(['error' => '']);

        $this->requestProcess->expects(self::never())
            ->method('processManualConfigure');

        $this->json->expects(self::once())
            ->method('setData')
            ->with([
                'error' => '',
                'success' => true
            ]);

        $result = $this->object->execute();
        self::assertInstanceOf(Json::class, $result);
    }
}
