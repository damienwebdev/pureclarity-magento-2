<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Signup;

use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Signup\AddStore;
use PHPUnit\Framework\MockObject\MockObject;
use PureClarity\Api\Signup\AddStore as ApiAddStore;
use PureClarity\Api\Signup\AddStoreFactory;
use Pureclarity\Core\Helper\Serializer;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Pureclarity\Core\Model\State;

/**
 * Class AddStoreTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Signup\AddStore
 */
class AddStoreTest extends TestCase
{
    const ACCESS_KEY = 'AccessKey1234';
    const SECRET_KEY = 'SecretKey1234';
    const REGION_ID = 1;
    const STORE_ID = 1;

    /** @var AddStore $object */
    private $object;

    /** @var MockObject|ApiAddStore $addStore */
    private $addStore;

    /** @var MockObject|AddStoreFactory $addStoreFactory */
    private $addStoreFactory;

    /** @var MockObject|Serializer $serializer*/
    private $serializer;

    /** @var MockObject|StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    protected function setUp()
    {
        $this->addStore = $this->getMockBuilder(ApiAddStore::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addStoreFactory = $this->getMockBuilder(AddStoreFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addStoreFactory->method('create')
            ->willReturn($this->addStore);

        $this->serializer = $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer->expects($this->any())->method('serialize')->will($this->returnCallback(function ($param) {
            return json_encode($param);
        }));

        $this->serializer->expects($this->any())->method('unserialize')->will($this->returnCallback(function ($param) {
            return json_decode($param, true);
        }));

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new AddStore(
            $this->addStoreFactory,
            $this->serializer,
            $this->stateRepository,
            $this->logger
        );
    }

    private function getDefaultParams()
    {
        return [
            'access_key' => self::ACCESS_KEY,
            'secret_key' => self::SECRET_KEY,
            'region' => self::REGION_ID,
            'store_id' => self::STORE_ID
        ];
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

        $state->method('getId')
            ->willReturn($id);

        $state->method('getStoreId')
            ->willReturn($storeId);

        $state->method('getName')
            ->willReturn($name);

        $state->method('getValue')
            ->willReturn($value);

        return $state;
    }

    public function testInstance()
    {
        self::assertInstanceOf(AddStore::class, $this->object);
    }

    public function testSendRequestErrors()
    {
        $params = $this->getDefaultParams();
        $params['platform'] = 'magento2';

        $this->addStore->expects(self::once())
            ->method('request')
            ->with($params)
            ->willReturn([
                'errors' => ['error1', 'error2']
            ]);

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(
            ['error' => 'error1, error2'],
            $result
        );
    }

    public function testSendRequest403Status()
    {
        $params = $this->getDefaultParams();
        $params['platform'] = 'magento2';

        $this->addStore->expects(self::once())
            ->method('request')
            ->with($params)
            ->willReturn([
                'errors' => '',
                'status' => 403
            ]);

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(
            [
                'error' => 'Account not found. Please check your Access Key & Secret Key are correct and try again.'
            ],
            $result
        );
    }

    public function testSendRequestNon200Status()
    {
        $params = $this->getDefaultParams();
        $params['platform'] = 'magento2';

        $this->addStore->expects(self::once())
            ->method('request')
            ->with($params)
            ->willReturn([
                'errors' => '',
                'status' => '400'
            ]);

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(
            [
                'error' => 'PureClarity server error occurred. If this persists,'
                    . ' please contact PureClarity support. Error code 400'
            ],
            $result
        );
    }

    public function testSendRequestInvalidResponse()
    {
        $params = $this->getDefaultParams();
        $params['platform'] = 'magento2';

        $this->addStore->expects(self::once())
            ->method('request')
            ->with($params)
            ->willReturn([
                'errors' => '',
                'status' => 200
            ]);

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(
            [
                'error' => 'An error occurred. If this persists, please contact PureClarity support.'
            ],
            $result
        );
    }

    public function testSendRequestValidResponse()
    {
        $params = $this->getDefaultParams();
        $params['platform'] = 'magento2';

        $this->addStore->expects(self::once())
            ->method('request')
            ->with($params)
            ->willReturn([
                'errors' => '',
                'status' => 200,
                'response' => [],
                'request_id' => 'abcdefghij'
            ]);

        $state = $this->getStateMock();

        $state->expects(self::once())
            ->method('setName')
            ->with('signup_request');

        $jsonRequest = json_encode([
            'id' => 'abcdefghij',
            'store_id' => self::STORE_ID,
            'region' =>  self::REGION_ID
        ]);

        $state->expects(self::once())
            ->method('setValue')
            ->with($jsonRequest);

        $state->expects(self::once())
            ->method('setStoreId')
            ->with(self::STORE_ID);

        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('signup_request', self::STORE_ID)
            ->willReturn($state);

        $expectedState = $this->getStateMock(null, 'signup_request', $jsonRequest, self::STORE_ID);

        $this->stateRepository->expects(self::once())
            ->method('save')
            ->with($expectedState);

        $result = $this->object->sendRequest($this->getDefaultParams());

        self::assertEquals(
            [
                'error' => ''
            ],
            $result
        );
    }

    public function testSendRequestBadSave()
    {
        $params = $this->getDefaultParams();
        $params['platform'] = 'magento2';

        $this->addStore->expects(self::once())
            ->method('request')
            ->with($params)
            ->willReturn([
                'errors' => '',
                'status' => 200,
                'response' => [],
                'request_id' => 'abcdefghij'
            ]);

        $state = $this->getStateMock();

        $state->expects(self::once())
            ->method('setName')
            ->with('signup_request');

        $jsonRequest = json_encode([
            'id' => 'abcdefghij',
            'store_id' => self::STORE_ID,
            'region' =>  self::REGION_ID
        ]);

        $state->expects(self::once())
            ->method('setValue')
            ->with($jsonRequest);

        $state->expects(self::once())
            ->method('setStoreId')
            ->with(self::STORE_ID);

        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('signup_request', self::STORE_ID)
            ->willReturn($state);

        $expectedState = $this->getStateMock(null, 'signup_request', $jsonRequest, self::STORE_ID);

        $this->stateRepository->expects(self::once())
            ->method('save')
            ->with($expectedState)
            ->willThrowException(new CouldNotSaveException(new Phrase('Some save error')));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity Add Store Error: could not save state: Some save error');

        $result = $this->object->sendRequest($this->getDefaultParams());

        self::assertEquals(
            [
                'error' => ''
            ],
            $result
        );
    }

    public function testSendRequestException()
    {
        $params = $this->getDefaultParams();
        $params['platform'] = 'magento2';

        $this->addStore->expects(self::once())
            ->method('request')
            ->with($params)
            ->willThrowException(new \Exception('Some error'));

        $result = $this->object->sendRequest($this->getDefaultParams());
        self::assertEquals(['error' => 'PureClarity Add Store Error: Some error'], $result);
    }
}
