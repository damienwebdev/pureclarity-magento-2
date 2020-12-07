<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Dashboard\NextStepsClick;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\NextSteps\Complete;

/**
 * Class NextStepsClickTest
 *
 * Tests the methods in \Pureclarity\Core\Controller\Adminhtml\Dashboard\NextStepsClick
 */
class NextStepsClickTest extends TestCase
{
    /** @var array $defaultParams */
    private $defaultParams = [
        'store' => '1',
        'next-step-id' => 'next-step-id-one-seven'
    ];

    /** @var NextStepsClick $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var MockObject|Complete $complete */
    private $complete;

    /** @var MockObject|Json $json */
    private $json;

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

        $this->context->method('getRequest')
            ->willReturn($this->request);

        $this->formKeyValidator = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->method('getFormKeyValidator')
            ->willReturn($this->formKeyValidator);

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory->method('create')
            ->willReturn($this->json);

        $this->complete = $this->getMockBuilder(Complete::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new NextStepsClick(
            $this->context,
            $this->jsonFactory,
            $this->complete
        );
    }

    private function setupRequestGetParams($params = [])
    {
        $this->request->expects(self::once())
            ->method('getParams')
            ->willReturn(empty($params) ? $this->defaultParams : $params);
    }

    private function setupRequestIsPost($response)
    {
        $this->request->expects(self::once())
            ->method('isPost')
            ->willReturn($response);
    }

    private function setupFormKeyValidator($response)
    {
        $this->formKeyValidator->expects(self::once())
            ->method('validate')
            ->willReturn($response);
    }

    public function testInstance()
    {
        self::assertInstanceOf(NextStepsClick::class, $this->object);
    }

    public function testAction()
    {
        self::assertInstanceOf(Action::class, $this->object);
    }

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

    public function testExecuteMissingStore()
    {
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);
        $this->setupRequestGetParams(['next-step-id' => 'one']);

        $this->json->expects(self::once())
            ->method('setData')
            ->with([
                'error' => 'Missing Store ID',
                'success' => false
            ]);

        $result = $this->object->execute();
        self::assertInstanceOf(Json::class, $result);
    }

    public function testExecuteComplete()
    {
        $this->setupRequestGetParams();
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);

        $this->complete->expects(self::once())
            ->method('markNextStepComplete')
            ->with('1','next-step-id-one-seven');

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
