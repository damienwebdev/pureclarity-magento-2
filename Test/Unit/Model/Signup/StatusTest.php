<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Signup;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Service\Url;
use Pureclarity\Core\Model\Signup\Status;
use Pureclarity\Core\Model\State;

/**
 * Class StatusTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class StatusTest extends TestCase
{
    const REQUEST_ID = 'abcdefghijklmonp';

    /** @var Status $object */
    private $object;

    /** @var mixed[] $requestParams */
    private $requestParams;

    /** @var Curl|MockObject $curlMock */
    private $curlMock;

    /** @var Url|MockObject $urlMock */
    private $urlMock;

    /** @var Json|MockObject $jsonMock */
    private $jsonMock;

    /** @var StateRepositoryInterface|MockObject $stateRepositoryMock */
    private $stateRepositoryMock;

    protected function setUp()
    {
        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlMock = $this->getMockBuilder(Url::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateRepositoryMock = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock->expects($this->any())->method('serialize')->will($this->returnCallback(function ($param) {
            return json_encode($param);
        }));

        $this->jsonMock->expects($this->any())->method('unserialize')->will($this->returnCallback(function ($param) {
            return json_decode($param, true);
        }));

        $this->object = new Status(
            $this->curlMock,
            $this->urlMock,
            $this->jsonMock,
            $this->stateRepositoryMock
        );
    }

    /**
     * @return MockObject
     */
    private function getRealStateObject()
    {
        $signupData = [
            'id' => self::REQUEST_ID,
            'store_id' => 1,
            'region' =>  1
        ];

        return $this->getStateMock(
            '1',
            'signup_request',
            json_encode($signupData),
            '0'
        );
    }

    /**
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $storeId
     * @return MockObject
     */
    private function getStateMock($id = null, $name = null, $value = null, $storeId = null)
    {
        $state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $state->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $state->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $state->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        $state->expects($this->any())
            ->method('getValue')
            ->willReturn($value);

        return $state;
    }

    public function testProcessInstance()
    {
        $this->assertInstanceOf(Status::class, $this->object);
    }

    public function testCheckStatusRequest()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $this->curlMock
            ->expects($this->once())
            ->method('post')
            ->will($this->returnCallback(function($url, $request) {
                $requestParams = json_decode($request, true);
                $this->requestParams = $requestParams;
            }));

        $json = [
            'Complete' => false
        ];

        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($json));
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(200);

        // Validate state save was called correctly with correct values
        $this->object->checkStatus();

        $this->assertArrayHasKey('Id', $this->requestParams);
        $this->assertEquals(self::REQUEST_ID, $this->requestParams['Id']);
    }

    public function testIncompleteCheckStatus()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $this->curlMock
            ->expects($this->once())
            ->method('post')
            ->will($this->returnCallback(function($url, $request) {
                $requestParams = json_decode($request, true);
                $this->requestParams = $requestParams;
            }));

        $json = [
            'Complete' => false
        ];

        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($json));
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(200);

        $result = $this->object->checkStatus();

        $this->assertEquals(false, $result['complete']);
        $this->assertEquals([], $result['response']);
        $this->assertEquals(
            '',
            $result['error']
        );
    }

    public function testCompleteCheckStatus()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $this->curlMock
            ->expects($this->once())
            ->method('post')
            ->will($this->returnCallback(function($url, $request) {
                $requestParams = json_decode($request, true);
                $this->requestParams = $requestParams;
            }));

        $json = [
            'Complete' => true,
            'AccessKey' => 'AccessKey1234',
            'SecretKey' => 'SecretKey1234'
        ];

        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($json));
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(200);

        $result = $this->object->checkStatus();

        $this->assertEquals(true, $result['complete']);
        $this->assertEquals([
            'access_key' => 'AccessKey1234',
            'secret_key' => 'SecretKey1234',
            'region' => 1,
            'store_id' => 1
        ], $result['response']);

        $this->assertEquals(
            '',
            $result['error']
        );
    }

    public function test400Response()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $json = [
            'errors' => [
                'An error'
            ]
        ];
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($json));
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(400);

        $result = $this->object->checkStatus();

        $this->assertEquals(false, $result['complete']);
        $this->assertEquals([], $result['response']);
        $this->assertEquals('Signup error: An error', $result['error']);
    }

    public function testUnexpectedResponse()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $json = [
            'errors' => [
                'An error'
            ]
        ];
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($json));
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(504);

        $result = $this->object->checkStatus();

        $this->assertEquals(false, $result['complete']);
        $this->assertEquals([], $result['response']);
        $this->assertEquals(
            'PureClarity server error occurred. If this persists, '
            . 'please contact PureClarity support. Error code 504',
            $result['error']
        );
    }

    public function testTimeoutResponse()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $this->curlMock->expects($this->any())
            ->method('post')
            ->willThrowException(new \Exception('Request timed out'));

        $result = $this->object->checkStatus();

        $this->assertEquals(false, $result['complete']);
        $this->assertEquals([], $result['response']);
        $this->assertEquals('Connection to PureClarity server timed out, please try again', $result['error']);
    }

    public function testGeneralException()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $this->curlMock->expects($this->any())
            ->method('post')
            ->willThrowException(new \Exception('Some other error'));

        $result = $this->object->checkStatus();

        $this->assertEquals(false, $result['complete']);
        $this->assertEquals([], $result['response']);
        $this->assertEquals('A general error occurred, please try again:Some other error', $result['error']);
    }
}
