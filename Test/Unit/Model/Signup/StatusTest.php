<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Signup;

use Magento\Framework\HTTP\Client\Curl;
use Pureclarity\Core\Helper\Serializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Service\Url;
use Pureclarity\Core\Model\Signup\Status;
use Pureclarity\Core\Model\State;

/**
 * Class StatusTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Signup\Status
 */
class StatusTest extends TestCase
{
    const REQUEST_ID = 'abcdefghijklmonp';

    /** @var Status $object */
    private $object;

    /** @var mixed[] $requestParams */
    private $requestParams;

    /** @var MockObject|Curl $curlMock */
    private $curlMock;

    /** @var MockObject|Url $urlMock */
    private $urlMock;

    /** @var MockObject|Serializer $serializerMock */
    private $serializerMock;

    /** @var MockObject|StateRepositoryInterface $stateRepositoryMock */
    private $stateRepositoryMock;

    protected function setUp()
    {
        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlMock = $this->getMockBuilder(Url::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateRepositoryMock = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock->expects($this->any())
            ->method('serialize')->will(
                $this->returnCallback(
                    function ($param) {
                        return json_encode($param);
                    }
                )
            );

        $this->serializerMock->expects($this->any())
            ->method('unserialize')->will(
                $this->returnCallback(
                    function ($param) {
                        return json_decode($param, true);
                    }
                )
            );

        $this->object = new Status(
            $this->curlMock,
            $this->urlMock,
            $this->serializerMock,
            $this->stateRepositoryMock
        );
    }

    /**
     * Generates a State Mock with signup data
     *
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
     * Generates a State Mock
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

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        $this->assertInstanceOf(Status::class, $this->object);
    }

    /**
     * Tests checkStatus handles a successful request
     */
    public function testCheckStatusRequest()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $this->curlMock
            ->expects($this->once())
            ->method('post')
            ->will($this->returnCallback(
                function ($url, $request) {
                    $requestParams = json_decode($request, true);
                    $this->requestParams = $requestParams;
                }
            ));

        $data = [
            'Complete' => false
        ];

        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($data));
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(200);

        // Validate state save was called correctly with correct values
        $this->object->checkStatus(1);

        $this->assertArrayHasKey('Id', $this->requestParams);
        $this->assertEquals(self::REQUEST_ID, $this->requestParams['Id']);
    }

    /**
     * Tests checkStatus handles an incomplete signup request
     */
    public function testIncompleteCheckStatus()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $this->curlMock
            ->expects($this->once())
            ->method('post')
            ->will($this->returnCallback(
                function ($url, $request) {
                    $requestParams = json_decode($request, true);
                    $this->requestParams = $requestParams;
                }
            ));

        $data = [
            'Complete' => false
        ];

        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($data));
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(200);

        $result = $this->object->checkStatus(1);

        $this->assertEquals(false, $result['complete']);
        $this->assertEquals([], $result['response']);
        $this->assertEquals(
            '',
            $result['error']
        );
    }

    /**
     * Tests checkStatus handles an complete signup request
     */
    public function testCompleteCheckStatus()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $this->curlMock
            ->expects($this->once())
            ->method('post')
            ->will($this->returnCallback(
                function ($url, $request) {
                    $requestParams = json_decode($request, true);
                    $this->requestParams = $requestParams;
                }
            ));

        $data = [
            'Complete' => true,
            'AccessKey' => 'AccessKey1234',
            'SecretKey' => 'SecretKey1234'
        ];

        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($data));
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(200);

        $result = $this->object->checkStatus(1);

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

    /**
     * Tests checkStatus handles 400 response
     */
    public function test400Response()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $data = [
            'errors' => [
                'An error'
            ]
        ];
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($data));
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(400);

        $result = $this->object->checkStatus(1);

        $this->assertEquals(false, $result['complete']);
        $this->assertEquals([], $result['response']);
        $this->assertEquals('Signup error: An error', $result['error']);
    }

    /**
     * Tests checkStatus handles an unexpected response format
     */
    public function testUnexpectedResponse()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $data = [
            'errors' => [
                'An error'
            ]
        ];
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($data));
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(504);

        $result = $this->object->checkStatus(1);

        $this->assertEquals(false, $result['complete']);
        $this->assertEquals([], $result['response']);
        $this->assertEquals(
            'PureClarity server error occurred. If this persists, '
            . 'please contact PureClarity support. Error code 504',
            $result['error']
        );
    }

    /**
     * Tests checkStatus handles a timeout
     */
    public function testTimeoutResponse()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $this->curlMock->expects($this->any())
            ->method('post')
            ->willThrowException(new \Exception('Request timed out'));

        $result = $this->object->checkStatus(1);

        $this->assertEquals(false, $result['complete']);
        $this->assertEquals([], $result['response']);
        $this->assertEquals('Connection to PureClarity server timed out, please try again', $result['error']);
    }

    /**
     * Tests checkStatus handles an Exception
     */
    public function testGeneralException()
    {
        $this->stateRepositoryMock->expects($this->once())
            ->method('getByNameAndStore')
            ->willReturn($this->getRealStateObject());

        $this->curlMock->expects($this->any())
            ->method('post')
            ->willThrowException(new \Exception('Some other error'));

        $result = $this->object->checkStatus(1);

        $this->assertEquals(false, $result['complete']);
        $this->assertEquals([], $result['response']);
        $this->assertEquals('A general error occurred, please try again:Some other error', $result['error']);
    }
}
