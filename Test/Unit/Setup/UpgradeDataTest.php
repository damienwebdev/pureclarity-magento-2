<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Setup;

use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\State;
use Pureclarity\Core\Setup\UpgradeData;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DataTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class UpgradeDataTest extends TestCase
{
    /** @var UpgradeData $object */
    private $object;

    /** @var MockObject|ModuleDataSetupInterface $setup */
    private $setup;

    /** @var MockObject|ModuleContextInterface $context */
    private $context;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var MockObject|StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    protected function setUp()
    {
        $this->setup = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(ModuleContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new UpgradeData(
            $this->coreConfig,
            $this->stateRepository,
            $this->storeManager,
            $this->logger
        );
    }

    private function setupGetVersion($version)
    {
        $this->context->expects($this->at(0))
            ->method('getVersion')
            ->willReturn('1.0.0');
    }

    private function setupGetStores()
    {
        $store1 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store1->expects($this->any())
            ->method('getId')
            ->willReturn('1');

        $store2 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store2->expects($this->any())
            ->method('getId')
            ->willReturn('2');

        $this->storeManager->expects($this->once())
            ->method('getStores')
            ->willReturn([$store1, $store2]);
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $storeId
     * @return MockObject
     */
    private function getStateMock($name = null, $value = null, $storeId = null)
    {
        $state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $state->expects($this->once())
            ->method('setStoreId')
            ->with($storeId);

        $state->expects($this->once())
            ->method('setName')
            ->with($name);

        $state->expects($this->once())
            ->method('setValue')
            ->with($value);

        return $state;
    }

    public function testInstance()
    {
        $this->assertInstanceOf(UpgradeData::class, $this->object);
    }

    public function testInterface()
    {
        $this->assertInstanceOf(UpgradeDataInterface::class, $this->object);
    }

    public function testUpgrade200NotConfigured()
    {
        $this->setupGetVersion('1.0.0');
        $this->setupGetStores();

        $this->setup->expects($this->once())->method('startSetup');
        $this->setup->expects($this->once())->method('endSetup');

        $this->coreConfig->expects($this->any())
            ->method('getAccessKey')
            ->willReturn(null);

        $this->stateRepository->expects($this->never())->method('getByNameAndStore');

        $this->object->upgrade($this->setup, $this->context);
    }

    public function testUpgrade200ConfiguredStore1()
    {
        $this->setupGetVersion('1.0.0');
        $this->setupGetStores();

        $this->setup->expects($this->once())->method('startSetup');
        $this->setup->expects($this->once())->method('endSetup');

        $this->coreConfig->expects($this->at(0))
            ->method('getAccessKey')
            ->with(1)
            ->willReturn('ACCESSKEY1234');

        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock('is_configured', '1', '0'));

        $this->stateRepository->expects($this->at(2))
            ->method('getByNameAndStore')
            ->with('default_store', 0)
            ->willReturn($this->getStateMock('default_store', '1', '0'));

        $this->object->upgrade($this->setup, $this->context);
    }

    public function testUpgrade200ConfiguredStore2()
    {
        $this->setupGetVersion('1.0.0');
        $this->setupGetStores();

        $this->setup->expects($this->once())->method('startSetup');
        $this->setup->expects($this->once())->method('endSetup');

        $this->coreConfig->expects($this->at(0))
            ->method('getAccessKey')
            ->with(1);

        $this->coreConfig->expects($this->at(1))
            ->method('getAccessKey')
            ->with(2)
            ->willReturn('ACCESSKEY1234');

        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock('is_configured', '1', '0'));

        $this->stateRepository->expects($this->at(2))
            ->method('getByNameAndStore')
            ->with('default_store', 0)
            ->willReturn($this->getStateMock('default_store', '2', '0'));

        $this->object->upgrade($this->setup, $this->context);
    }

    public function testUpgrade200Exception()
    {
        $this->setupGetVersion('1.0.0');
        $this->setupGetStores();

        $this->setup->expects($this->once())->method('startSetup');
        $this->setup->expects($this->once())->method('endSetup');

        $this->coreConfig->expects($this->at(0))
            ->method('getAccessKey')
            ->with(1)
            ->willReturn('ACCESSKEY1234');

        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('is_configured', 0)
            ->willReturn($this->getStateMock('is_configured', '1', '0'));

        $this->stateRepository->expects($this->any())
            ->method('save')
            ->willThrowException(new CouldNotSaveException(new Phrase('An Error')));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('PureClarity: could not set state on upgrade: An Error');

        $this->object->upgrade($this->setup, $this->context);
    }

    public function testNoUpgrade()
    {
        $this->context->expects($this->at(0))
            ->method('getVersion')
            ->willReturn('9.9.9');

        $this->setup->expects($this->never())->method('startSetup');
        $this->setup->expects($this->never())->method('endSetup');
        $this->object->upgrade($this->setup, $this->context);
    }
}
