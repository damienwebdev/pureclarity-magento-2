<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Signup;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Cron;
use Pureclarity\Core\Model\Signup\Process;
use Pureclarity\Core\Model\CronFactory;
use Pureclarity\Core\Model\State;
use Psr\Log\LoggerInterface;

/**
 * Class ProcessTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Signup\Process
 */
class ProcessTest extends TestCase
{
    const ACCESS_KEY = 'AccessKey1234';
    const SECRET_KEY = 'SecretKey1234';
    const REGION_ID = 1;
    const STORE_ID = 1;

    /** @var Process $object */
    private $object;

    /** @var MockObject|StateRepositoryInterface $stateRepositoryInterfaceMock */
    private $stateRepositoryInterfaceMock;

    /** @var MockObject|CoreConfig $coreConfigMock */
    private $coreConfigMock;

    /** @var MockObject|CronFactory $cronFactoryMock */
    private $cronFactoryMock;

    /** @var MockObject|Cron $cronMock */
    private $cronMock;

    /** @var MockObject|StoreManagerInterface $storeManagerInterfaceMock */
    private $storeManagerInterfaceMock;

    /** @var MockObject|StoreInterface $storeInterfaceMock */
    private $storeInterfaceMock;

    /** @var MockObject|Manager $cacheManagerMock */
    private $cacheManagerMock;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    protected function setUp()
    {
        $this->stateRepositoryInterfaceMock = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreConfigMock = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cronFactoryMock = $this->getMockBuilder(CronFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cronMock = $this->getMockBuilder(Cron::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cronFactoryMock->expects($this->any())->method('create')
            ->will($this->returnValue($this->cronMock));

        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheManagerMock = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Process(
            $this->stateRepositoryInterfaceMock,
            $this->coreConfigMock,
            $this->cronFactoryMock,
            $this->storeManagerInterfaceMock,
            $this->cacheManagerMock,
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
        $this->assertInstanceOf(Process::class, $this->object);
    }

    public function testProcess()
    {
        $this->coreConfigMock->expects($this->exactly(1))
            ->method('setAccessKey');

        $this->coreConfigMock->expects($this->exactly(1))
            ->method('setSecretKey');

        $this->coreConfigMock->expects($this->exactly(1))
            ->method('setRegion');

        $this->coreConfigMock->expects($this->exactly(1))
            ->method('setIsActive');

        $this->coreConfigMock->expects($this->exactly(1))
            ->method('setDeltasEnabled');

        $this->coreConfigMock->expects($this->exactly(1))
            ->method('setIsDailyFeedActive');

        // test saveConfig calls
        $this->coreConfigMock->expects($this->at(0))
            ->method('setAccessKey')
            ->with(self::ACCESS_KEY, self::STORE_ID);

        $this->coreConfigMock->expects($this->at(1))
            ->method('setSecretKey')
            ->with(self::SECRET_KEY, self::STORE_ID);

        $this->coreConfigMock->expects($this->at(2))
            ->method('setRegion')
            ->with(self::REGION_ID, self::STORE_ID);

        $this->coreConfigMock->expects($this->at(3))
            ->method('setIsActive')
            ->with(1, self::STORE_ID);

        $this->coreConfigMock->expects($this->at(4))
            ->method('setDeltasEnabled')
            ->with(1, self::STORE_ID);

        $this->coreConfigMock->expects($this->at(5))
            ->method('setIsDailyFeedActive')
            ->with(1, self::STORE_ID);

        $this->cacheManagerMock->expects($this->at(0))
            ->method('clean')
            ->with([Config::TYPE_IDENTIFIER]);

        // test setWelcomeState calls

        $this->stateRepositoryInterfaceMock->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->stateRepositoryInterfaceMock->expects($this->at(1))
            ->method('save')
            ->with($this->getStateMock('1', 'show_welcome_banner', 'auto', self::STORE_ID));

        // test completeSignup calls
        $this->stateRepositoryInterfaceMock->expects($this->at(2))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'signup_request', 'complete', self::STORE_ID));

        $this->stateRepositoryInterfaceMock->expects($this->at(3))
            ->method('delete')
            ->with($this->getStateMock('1', 'signup_request', 'complete', self::STORE_ID));

        // test triggerFeeds calls
        $this->cronMock->expects($this->at(0))
            ->method('scheduleSelectedFeeds')
            ->with(self::STORE_ID, ['product', 'category', 'user', 'orders']);

        $this->object->process($this->getDefaultParams());
    }

    public function testProcessWithDefaultStore()
    {
        $this->stateRepositoryInterfaceMock->expects($this->any())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        // test triggerFeeds calls
        $this->cronMock->expects($this->at(0))
            ->method('scheduleSelectedFeeds')
            ->with(17, ['product', 'category', 'user', 'orders']);

        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getDefaultStoreView')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(17);

        $params = $this->getDefaultParams();
        $params['store_id'] = 0;

        $this->object->process($params);
    }

    public function testProcessSaveError()
    {
        $this->stateRepositoryInterfaceMock->expects($this->any())
            ->method('save')
            ->willThrowException(new CouldNotSaveException(new Phrase('Some save error')));

        $this->stateRepositoryInterfaceMock->expects($this->any())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $result = $this->object->process($this->getDefaultParams());
        $this->assertEquals(['Error processing request: Some save error'], $result['errors']);
    }

    public function testProcessDeleteError()
    {
        $this->stateRepositoryInterfaceMock->expects($this->any())
            ->method('delete')
            ->willThrowException(new CouldNotDeleteException(new Phrase('Some delete error')));

        $this->stateRepositoryInterfaceMock->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->stateRepositoryInterfaceMock->expects($this->at(1))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->stateRepositoryInterfaceMock->expects($this->at(2))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'signup_request', '', self::STORE_ID));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('PureClarity: could not clear signup state. Error was: Some delete error');

        $this->object->process($this->getDefaultParams());
    }

    public function testManualConfigureWithEmptyParams()
    {
        $result = $this->object->processManualConfigure([]);

        $expectedErrors = [
            'Missing Access Key',
            'Missing Secret Key',
            'Missing Region',
            'Missing Store ID'
        ];

        $this->assertEquals($expectedErrors, $result['errors']);
    }

    public function testManualConfigureWithInvalidParams()
    {
        $params = $this->getDefaultParams();
        $params['access_key'] = '';
        $result = $this->object->processManualConfigure($params);
        $expectedErrors = ['Missing Access Key'];
        $this->assertEquals($expectedErrors, $result['errors']);

        $params = $this->getDefaultParams();
        $params['secret_key'] = '';
        $result = $this->object->processManualConfigure($params);
        $expectedErrors = ['Missing Secret Key'];
        $this->assertEquals($expectedErrors, $result['errors']);

        $params = $this->getDefaultParams();
        $params['region'] = '';
        $result = $this->object->processManualConfigure($params);
        $expectedErrors = ['Missing Region'];
        $this->assertEquals($expectedErrors, $result['errors']);
    }

    public function testManualConfigureWithValidParams()
    {
        $this->stateRepositoryInterfaceMock->expects($this->any())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $result = $this->object->processManualConfigure($this->getDefaultParams());
        $this->assertEquals([], $result['errors']);
    }

    public function testManualConfigureWithSaveError()
    {
        $this->stateRepositoryInterfaceMock->expects($this->any())
            ->method('save')
            ->willThrowException(new CouldNotSaveException(new Phrase('Some save error')));

        $this->stateRepositoryInterfaceMock->expects($this->any())
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $result = $this->object->processManualConfigure($this->getDefaultParams());
        $this->assertEquals(['Error processing request: Some save error'], $result['errors']);
    }
}
