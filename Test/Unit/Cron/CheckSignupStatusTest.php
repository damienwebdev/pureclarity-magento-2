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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Cron\CheckSignupStatus;
use Magento\Framework\Controller\Result\Json;
use Pureclarity\Core\Model\Signup\Process;
use Pureclarity\Core\Model\Signup\Status as RequestStatus;
use Pureclarity\Core\Model\State as StateModel;

/**
 * Class CheckSignupStatusTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class CheckSignupStatusTest extends TestCase
{
    /** @var array $defaultParams */
    private $defaultParams = [
        'param1' => 'param1',
        'param2' => 'param1',
    ];

    /** @var CheckSignupStatus $object */
    private $object;

    /** @var RequestStatus $requestStatus */
    private $requestStatus;

    /** @var Process $requestProcess */
    private $requestProcess;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var LoggerInterface $logger*/
    private $logger;

    /** @var State $state*/
    private $state;

    protected function setUp()
    {
        $this->requestStatus = $this->getMockBuilder(RequestStatus::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestProcess = $this->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new CheckSignupStatus(
            $this->requestStatus,
            $this->requestProcess,
            $this->stateRepository,
            $this->logger,
            $this->state
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
        $state = $this->getMockBuilder(StateModel::class)
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

    public function testInstance()
    {
        $this->assertInstanceOf(CheckSignupStatus::class, $this->object);
    }

    public function testExecuteSetAreaCodeException()
    {
        $this->state->expects($this->once())
            ->method('setAreaCode')
            ->with(Area::AREA_ADMINHTML)
            ->willThrowException(new LocalizedException(new Phrase('Area code already set')));

        $this->stateRepository->expects($this->once())
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock(1, 'is_configured', '1', 0));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('PureClarity Setup Warning: Area code already set');

        $this->object->execute();
    }

    public function testExecuteAlreadyConfigured()
    {
        $this->stateRepository->expects($this->exactly(1))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock(1, 'is_configured', '1', 0));

        $this->requestStatus->expects($this->never())
            ->method('checkStatus');

        $this->object->execute();
    }

    public function testExecuteNotConfiguredNoSignup()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($this->getStateMock());

        $this->requestStatus->expects($this->never())
            ->method('checkStatus');

        $this->object->execute();
    }

    public function testExecuteNotConfiguredSignupComplete()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($this->getStateMock(1, 'signup_request', 'complete', 0));

        $this->requestStatus->expects($this->never())
            ->method('checkStatus');

        $this->object->execute();
    }

    public function testExecuteNotConfiguredNotComplete()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($this->getStateMock(1, 'signup_request', 'in-progress', 0));

        $this->requestStatus->expects($this->exactly(1))
            ->method('checkStatus')
            ->willReturn([
                'complete' => false,
                'error' => ''
            ]);

        $this->requestProcess->expects($this->never())
            ->method('process');

        $this->object->execute();
    }

    public function testExecuteNotConfiguredError()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($this->getStateMock(1, 'signup_request', 'in-progress', 0));

        $this->requestStatus->expects($this->exactly(1))
            ->method('checkStatus')
            ->willReturn([
                'complete' => false,
                'error' => 'some error'
            ]);

        $this->requestProcess->expects($this->never())
            ->method('process');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('PureClarity Setup Error: some error');

        $this->object->execute();
    }

    public function testExecuteComplete()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->with('signup_request', 0)
            ->willReturn($this->getStateMock(1, 'signup_request', 'in-progress', 0));

        $this->requestStatus->expects($this->exactly(1))
            ->method('checkStatus')
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
}
