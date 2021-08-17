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

    protected function setUp(): void
    {
        $this->addStore = $this->createMock(ApiAddStore::class);
        $this->addStoreFactory = $this->createMock(AddStoreFactory::class);

        $this->addStoreFactory->method('create')
            ->willReturn($this->addStore);

        $this->serializer = $this->createMock(Serializer::class);

        $this->serializer->expects($this->any())->method('serialize')->will($this->returnCallback(function ($param) {
            return json_encode($param);
        }));

        $this->serializer->expects($this->any())->method('unserialize')->will($this->returnCallback(function ($param) {
            return json_decode($param, true);
        }));

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->stateRepository = $this->createMock(StateRepositoryInterface::class);

        $this->object = new AddStore(
            $this->addStoreFactory,
            $this->serializer,
            $this->stateRepository,
            $this->logger
        );
    }

    /**
     * Returns default params for use with calls
     * @return array
     */
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
     * Generates a State mock
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $storeId
     * @return MockObject
     */
    private function getStateMock($id = null, $name = null, $value = null, $storeId = null)
    {
        $state = $this->createMock(State::class);

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

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(AddStore::class, $this->object);
    }

    /**
     * Tests how sendRequest handles an error response
     */
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

    /**
     * Tests how sendRequest handles a 403 response
     */
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

    /**
     * Tests how sendRequest handles a non-200/non-403 response
     */
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

    /**
     * Tests how sendRequest handles an invalid response
     */
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

    /**
     * Tests how sendRequest handles a valid response
     */
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

    /**
     * Tests how sendRequest handles a bad attempt at saving the signup request
     */
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

    /**
     * Tests how sendRequest handles an Exception thrown by the addStore->request method
     */
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
