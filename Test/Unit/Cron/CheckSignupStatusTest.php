<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Cron;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Cron\CheckSignupStatus;
use Pureclarity\Core\Model\Signup\Process;
use Pureclarity\Core\Model\Signup\Status as RequestStatus;
use Pureclarity\Core\Model\State as StateModel;

/**
 * Class CheckSignupStatusTest
 *
 * Tests the methods in \Pureclarity\Core\Cron\CheckSignupStatus
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckSignupStatusTest extends TestCase
{
    /** @var array $defaultParams */
    private $defaultParams = [
        'param1' => 'param1',
        'param2' => 'param1',
    ];

    /** @var MockObject|CheckSignupStatus $object */
    private $object;

    /** @var MockObject|RequestStatus $requestStatus */
    private $requestStatus;

    /** @var MockObject|Process $requestProcess */
    private $requestProcess;

    /** @var MockObject|StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var MockObject|LoggerInterface $logger*/
    private $logger;

    /** @var MockObject|State $state*/
    private $state;

    /** @var MockObject|StoreManagerInterface $storeManager */
    private $storeManager;

    protected function setUp(): void
    {
        $this->requestStatus = $this->createMock(RequestStatus::class);
        $this->requestProcess = $this->createMock(Process::class);
        $this->stateRepository = $this->createMock(StateRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->state = $this->createMock(State::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->object = new CheckSignupStatus(
            $this->requestStatus,
            $this->requestProcess,
            $this->stateRepository,
            $this->logger,
            $this->state,
            $this->storeManager
        );
    }

    /**
     * Returns a State mock
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $storeId
     * @return MockObject
     */
    private function getStateMock($id = null, $name = null, $value = null, $storeId = null)
    {
        $state = $this->createMock(StateModel::class);

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
     * Sets the expected return value on storeManager > hasSingleStore
     * @param bool $return
     */
    private function setupHasSingleStore($return)
    {
        $this->storeManager->expects($this->exactly(1))
            ->method('hasSingleStore')
            ->willReturn($return);
    }

    private function setupGetStores()
    {
        $store1 = $this->createMock(StoreInterface::class);

        $store1->method('getId')
            ->willReturn('1');

        $store2 = $this->createMock(StoreInterface::class);

        $store2->method('getId')
            ->willReturn('17');

        $this->storeManager->expects($this->at(1))
            ->method('getStores')
            ->willReturn([$store1, $store2]);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        $this->assertInstanceOf(CheckSignupStatus::class, $this->object);
    }

    /**
     * Tests how execute handles the 'Area code already set' exception
     */
    public function testExecuteSetAreaCodeException()
    {
        $this->setupHasSingleStore(true);

        $this->stateRepository->expects($this->exactly(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($this->getStateMock());

        $this->state->expects($this->once())
            ->method('setAreaCode')
            ->with(Area::AREA_ADMINHTML)
            ->willThrowException(new LocalizedException(new Phrase('Area code already set')));

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with no signup request present
     */
    public function testSingleStoreNoRequest()
    {
        $this->setupHasSingleStore(true);

        $this->stateRepository->expects($this->exactly(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($this->getStateMock(null, '', '', 0));

        $this->requestStatus->expects($this->never())
            ->method('checkStatus');

        $this->requestProcess->expects($this->never())
            ->method('process');

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with an incomplete signup request present
     */
    public function testSingleStoreIncomplete()
    {
        $this->setupHasSingleStore(true);
        $this->stateRepository->expects($this->exactly(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($this->getStateMock(1, 'signup_request', '{}', 0));

        $this->requestStatus->expects($this->once())
            ->method('checkStatus')
            ->with(0)
            ->willReturn([
                'complete' => false,
                'error' => ''
            ]);

        $this->requestProcess->expects($this->never())
            ->method('process');

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with an error in checkStatus
     */
    public function testSingleStoreError()
    {
        $this->setupHasSingleStore(true);
        $this->stateRepository->expects($this->exactly(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($this->getStateMock(1, 'signup_request', '{}', 0));

        $this->requestStatus->expects($this->once())
            ->method('checkStatus')
            ->with(0)
            ->willReturn([
                'complete' => false,
                'error' => 'There was an error'
            ]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('PureClarity Setup Error: There was an error');

        $this->requestProcess->expects($this->never())
            ->method('process');

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with a complete signup request
     */
    public function testSingleStoreComplete()
    {
        $this->setupHasSingleStore(true);
        $this->stateRepository->expects($this->exactly(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($this->getStateMock(1, 'signup_request', '{}', 0));

        $this->requestStatus->expects($this->once())
            ->method('checkStatus')
            ->with(0)
            ->willReturn([
                'complete' => true,
                'error' => '',
                'response' => $this->defaultParams
            ]);

        $this->requestProcess->expects($this->once())
            ->method('process')
            ->with($this->defaultParams);

        $this->logger->expects($this->never())
            ->method('error');

        $this->object->execute();
    }

    /**
     * Tests how execute handles a multi store setup with no signup requests
     */
    public function testMultiStoreNoRequests()
    {
        $this->setupHasSingleStore(false);
        $this->setupGetStores();

        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('signup_request', 1)
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 17)
            ->willReturn($this->getStateMock());

        $this->requestStatus->expects($this->never())
            ->method('checkStatus');

        $this->requestProcess->expects($this->never())
            ->method('process');

        $this->logger->expects($this->never())
            ->method('error');

        $this->object->execute();
    }

    /**
     * Tests how execute handles a multi store setup with one signup request
     */
    public function testMultiStoreOneRequest()
    {
        $this->setupHasSingleStore(false);
        $this->setupGetStores();

        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('signup_request', 1)
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 17)
            ->willReturn($this->getStateMock(1));

        $this->requestStatus->expects($this->once())
            ->method('checkStatus')
            ->with(17)
            ->willReturn([
                'complete' => false,
                'error' => '',
                'response' => $this->defaultParams
            ]);

        $this->requestProcess->expects($this->never())
            ->method('process');

        $this->logger->expects($this->never())
            ->method('error');

        $this->object->execute();
    }

    /**
     * Tests how execute handles a multi store setup with every store having a signup request
     */
    public function testMultiStoreAllRequests()
    {
        $this->setupHasSingleStore(false);
        $this->setupGetStores();

        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('signup_request', 1)
            ->willReturn($this->getStateMock(1, 'signup_request', '{}', 1));

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 17)
            ->willReturn($this->getStateMock(2, 'signup_request', '{}', 17));

        $this->requestStatus->expects($this->at(0))
            ->method('checkStatus')
            ->with(1)
            ->willReturn([
                'complete' => false,
                'error' => '',
                'response' => $this->defaultParams
            ]);

        $this->requestStatus->expects($this->at(1))
            ->method('checkStatus')
            ->with(17)
            ->willReturn([
                'complete' => false,
                'error' => '',
                'response' => $this->defaultParams
            ]);

        $this->object->execute();
    }
}
