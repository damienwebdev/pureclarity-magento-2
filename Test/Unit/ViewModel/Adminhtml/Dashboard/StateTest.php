<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml\Dashboard;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\State;
use Pureclarity\Core\Model\State as StateModel;

/**
 * Class StateTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Dashboard\State
 */
class StateTest extends TestCase
{
    /** @var State $object */
    private $object;

    /** @var MockObject|StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var MockObject|ProductMetadataInterface $productMetadata */
    private $productMetadata;

    protected function setUp()
    {
        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMetadata = $this->getMockBuilder(ProductMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new State(
            $this->stateRepository,
            $this->productMetadata,
            $request,
            $coreConfig
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
        $this->assertInstanceOf(State::class, $this->object);
    }

    public function testGetStateNameNotConfigured()
    {
        $this->stateRepository->expects($this->atMost(2))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->assertEquals(State::STATE_NOT_CONFIGURED, $this->object->getStateName(1));
    }

    public function testGetStateNameWaiting()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'signup_request', 'notcomplete', '0'));

        $this->assertEquals(State::STATE_WAITING, $this->object->getStateName(1));
    }

    public function testGetStateNameConfigured()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'is_configured', '1', '0'));

        $this->assertEquals(State::STATE_CONFIGURED, $this->object->getStateName(1));
    }

    public function testIsWaitingTrue()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'signup_request', 'notcomplete', '0'));

        $this->assertEquals(true, $this->object->isWaiting(1));
    }

    public function testIsWaitingFalseConfigured()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'is_configured', '1', '0'));

        $this->assertEquals(false, $this->object->isWaiting(1));
    }

    public function testIsWaitingFalseNotStarted()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->assertEquals(false, $this->object->isWaiting(1));
    }

    public function testGetPluginVersion()
    {
        $this->assertEquals(Data::CURRENT_VERSION, $this->object->getPluginVersion());
    }

    public function testIsUpToDateTrueEmptyNewVersion()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->assertEquals(true, $this->object->isUpToDate());
    }

    public function testIsUpToDateTrueNewVersionMatches()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'new_version', Data::CURRENT_VERSION, '0'));

        $this->assertEquals(true, $this->object->isUpToDate());
    }

    public function testIsUpToDateFalse()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'new_version', '9.9.9', '0'));

        $this->assertEquals(false, $this->object->isUpToDate());
    }

    public function testGetNewVersion()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock('1', 'new_version', '9.9.9', '0'));

        $this->assertEquals('9.9.9', $this->object->getNewVersion());
    }

    public function testGetNewVersionEmpty()
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->willReturn($this->getStateMock());

        $this->assertEquals('', $this->object->getNewVersion());
    }

    public function testGetMagentoVersion()
    {
        $this->productMetadata->expects($this->at(0))
            ->method('getVersion')
            ->willReturn('2.3.2');

        $this->productMetadata->expects($this->at(1))
            ->method('getEdition')
            ->willReturn('Commerce');

        $this->assertEquals('2.3.2 Commerce', $this->object->getMagentoVersion());
    }
}
