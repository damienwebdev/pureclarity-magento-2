<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Controller\Adminhtml\Dashboard\GetStoreDetails;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Pureclarity\Core\Helper\StoreData;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class GetStoreDetailsTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class GetStoreDetailsTest extends TestCase
{
    /** @var array $defaultParams */
    private $defaultParams = [
        'store_id' => '1'
    ];

    /** @var array $defaultParams */
    private $errorParams = [
        'param1' => 'param1',
        'param2' => 'param1',
    ];

    /** @var GetStoreDetails $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var MockObject|Json $json */
    private $json;

    /** @var MockObject|Validator $formKeyValidator */
    private $formKeyValidator;

    /** @var MockObject|StoreData $storeData */
    private $storeData;

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

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeData = $this->getMockBuilder(StoreData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->json);

        $this->object = new GetStoreDetails(
            $this->context,
            $this->jsonFactory,
            $this->storeData
        );
    }

    private function setupRequestGetParams($error = false)
    {
        $params = $error ? $this->errorParams : $this->defaultParams;
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
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
        $this->assertInstanceOf(GetStoreDetails::class, $this->object);
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
                'success' => false,
                'store_data'=> []
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
                'success' => false,
                'store_data'=> []
            ]);

        $result = $this->object->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteInvalidParam()
    {
        $this->setupRequestGetParams(true);
        $this->setupRequestIsPost(true);
        $this->setupFormKeyValidator(true);

        $this->json->expects($this->once())
            ->method('setData')
            ->with([
                'error' => 'Missing Store ID',
                'success' => false,
                'store_data'=> []
            ]);

        $result = $this->object->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteSuccess()
    {
        $this->setupRequestIsPost(true);
        $this->setupRequestGetParams();
        $this->setupFormKeyValidator(true);

        $this->storeData->expects($this->once())
            ->method('getStoreCurrency')
            ->with(1)
            ->willReturn('GBP');

        $this->storeData->expects($this->once())
            ->method('getStoreTimezone')
            ->with(1)
            ->willReturn('Europe/London');

        $this->storeData->expects($this->once())
            ->method('getStoreURL')
            ->with(1)
            ->willReturn('http://www.google.com/');

        $this->json->expects($this->once())
            ->method('setData')
            ->with([
                'error' => '',
                'success' => true,
                'store_data' => [
                    'currency' => 'GBP',
                    'timezone' => 'Europe/London',
                    'url' => 'http://www.google.com/'
                ]
            ]);

        $result = $this->object->execute();
        $this->assertInstanceOf(Json::class, $result);
    }
}
