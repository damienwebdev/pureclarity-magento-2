<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Phrase;
use Pureclarity\Core\Helper\Serializer;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\FeedStatus;
use Pureclarity\Core\Model\State;

/**
 * Class DataTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class FeedStatusTest extends TestCase
{
    /** @var FeedStatus $object */
    private $object;

    /** @var StateRepositoryInterface */
    private $stateRepository;

    /** @var Filesystem */
    private $fileSystem;

    /** @var Data */
    private $coreHelper;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var Serializer */
    private $serializer;

    /** @var TimezoneInterface */
    private $timezone;

    /** @var ReadInterface */
    private $readInterface;

    /** @var LoggerInterface */
    private $logger;

    protected function setUp()
    {
        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileSystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->timezone = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->readInterface = $this->getMockBuilder(ReadInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new FeedStatus(
            $this->stateRepository,
            $this->fileSystem,
            $this->coreHelper,
            $this->coreConfig,
            $this->serializer,
            $this->timezone,
            $this->logger
        );

        $this->setDefaultMockBehaviour();
    }

    /**
     * Sets default behaviours for various mocks used in this test
     */
    private function setDefaultMockBehaviour()
    {
        $this->serializer->expects($this->any())->method('serialize')->will($this->returnCallback(function ($param) {
            return json_encode($param);
        }));

        $this->serializer->expects($this->any())->method('unserialize')->will($this->returnCallback(function ($param) {
            return json_decode($param, true);
        }));

        $this->readInterface->expects($this->any())->method('isExist')->willReturn(true);
        $this->fileSystem->expects($this->any())->method('getDirectoryRead')->willReturn($this->readInterface);

        $this->coreHelper->expects($this->any())
            ->method('getProgressFileName')
            ->willReturn('progress_filename');

        $this->coreHelper->method('getPureClarityBaseDir')->willReturn('path');
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

    /**
     * Sets up a default state object to return for "running_feeds" state row
     *
     * @param int $id
     * @param string $value
     */
    private function initRunningFeedsStateObject($id = null, $value = null)
    {
        $this->stateRepository->expects($this->at(0))
            ->method('getByNameAndStore')
            ->with('running_feeds')
            ->willReturn($this->getStateMock($id, 'running_feeds', json_encode($value), '0'));
    }

    /**
     * Sets up a default state object to return for "last_product_feed_date" state row
     *
     * @param int $id
     * @param string $value
     */
    private function initDateStateObject($id = null, $value = null)
    {
        $this->stateRepository->expects($this->at(1))
            ->method('getByNameAndStore')
            ->with('last_product_feed_date')
            ->willReturn($this->getStateMock($id, 'last_product_feed_date', $value, '0'));
    }

    /**
     * Sets up the file contents returned by reading the readFile call for the scheduled feed file
     */
    private function initScheduleFileRead()
    {
        $schedule = [
            'store' => 0,
            'feeds' => ['product']
        ];

        $this->readInterface->expects($this->at(1))
            ->method('readFile')
            ->with('path' . DIRECTORY_SEPARATOR . 'scheduled_feed', null, null)
            ->willReturn(json_encode($schedule));
    }

    public function testFeedStatusInstance()
    {
        $this->assertInstanceOf(FeedStatus::class, $this->object);
    }

    public function testGetFeedStatusNotSent()
    {
        $this->initRunningFeedsStateObject();
        $this->initDateStateObject();
        $status = $this->object->getFeedStatus('product');

        $this->assertEquals(
            [
                'enabled' => true,
                'running' => false,
                'class' => 'pc-feed-not-sent',
                'label' => 'Not Sent',
            ],
            $status
        );
    }

    public function testGetFeedStatusRequested()
    {
        $this->initRunningFeedsStateObject();
        $this->initScheduleFileRead();

        $status = $this->object->getFeedStatus('product');

        $this->assertEquals(
            [
                'enabled' => true,
                'running' => true,
                'class' => 'pc-feed-waiting',
                'label' => 'Waiting for feed run to start',
            ],
            $status
        );
    }

    public function testGetFeedStatusWaiting()
    {
        $this->initRunningFeedsStateObject(1, ['product']);
        $this->initScheduleFileRead();

        $status = $this->object->getFeedStatus('product');

        $this->assertEquals(
            [
                'enabled' => true,
                'running' => true,
                'class' => 'pc-feed-waiting',
                'label' => 'Waiting for other feeds to finish',
            ],
            $status
        );
    }

    public function testGetFeedStatusInProgress()
    {
        $this->initRunningFeedsStateObject(1, ['product']);

        $progress = [
            'name' => 'product',
            'cur' => '1',
            'max' => '4',
            'isComplete' => false,
            'isUploaded' => false,
            'error' => ''
        ];

        $this->readInterface->expects($this->at(3))
            ->method('readFile')
            ->with('progress_filename', null, null)
            ->willReturn(json_encode($progress));

        $status = $this->object->getFeedStatus('product');

        $this->assertEquals(
            [
                'enabled' => true,
                'running' => true,
                'class' => 'pc-feed-in-progress',
                'label' => 'In progress: 25%',
            ],
            $status
        );
    }

    public function testGetFeedStatusComplete()
    {
        $this->initRunningFeedsStateObject();
        $this->initDateStateObject(1, '2019-10-15 15:45:00');

        $this->timezone->expects($this->any())
            ->method('formatDate')
            ->with('2019-10-15 15:45:00', \IntlDateFormatter::SHORT, true)
            ->willReturn('15/10/2019 15:45');

        $status = $this->object->getFeedStatus('product');

        $this->assertEquals(
            [
                'enabled' => true,
                'running' => false,
                'class' => 'pc-feed-complete',
                'label' => 'Last sent 15/10/2019 15:45',
            ],
            $status
        );
    }

    public function testGetFeedStatusNotEnabled()
    {
        $this->coreConfig->expects($this->at(0))
            ->method('isActive')
            ->with(1)
            ->willReturn(false);

        $status = $this->object->getFeedStatus('product', 1);

        $this->assertEquals(
            [
                'enabled' => false,
                'running' => false,
                'class' => 'pc-feed-disabled',
                'label' => 'Not Enabled',
            ],
            $status
        );
    }

    public function testGetFeedStatusNotEnabledBrands()
    {
        $this->coreConfig->expects($this->at(0))
            ->method('isActive')
            ->with(1)
            ->willReturn(true);

        $this->coreConfig->expects($this->at(1))
            ->method('isBrandFeedEnabled')
            ->with(1)
            ->willReturn(false);

        $status = $this->object->getFeedStatus('brand', 1);

        $this->assertEquals(
            [
                'enabled' => false,
                'running' => false,
                'class' => 'pc-feed-disabled',
                'label' => 'Not Enabled',
            ],
            $status
        );
    }

    public function testGetAreFeedsInProgressTrue()
    {
        $this->initRunningFeedsStateObject(1, ['product']);

        $progress = [
            'name' => 'product',
            'cur' => '1',
            'max' => '4',
            'isComplete' => false,
            'isUploaded' => false,
            'error' => ''
        ];

        $this->readInterface->expects($this->at(3))
            ->method('readFile')
            ->with('progress_filename', null, null)
            ->willReturn(json_encode($progress));

        $status = $this->object->getAreFeedsInProgress(['product'], 1);
        $this->assertEquals(true, $status);
    }

    public function testGetAreFeedsDisabledFalse()
    {
        $this->initRunningFeedsStateObject();
        $this->initDateStateObject();

        $status = $this->object->getAreFeedsDisabled(['product'], 1);
        $this->assertEquals(false, $status);
    }

    public function testGetAreFeedsDisabledTrue()
    {
        $this->coreConfig->expects($this->any())
            ->method('isActive')
            ->with(1)
            ->willReturn(false);

        $status = $this->object->getAreFeedsDisabled(['product'], 1);
        $this->assertEquals(true, $status);
    }

    public function testGetAreFeedsInProgressFalse()
    {
        $this->initRunningFeedsStateObject();
        $this->initDateStateObject();

        $status = $this->object->getAreFeedsInProgress(['product'], 1);
        $this->assertEquals(false, $status);
    }

    public function testGetScheduledDataException()
    {
        $this->initRunningFeedsStateObject();
        $this->initDateStateObject();

        $this->readInterface->expects($this->at(1))
            ->method('readFile')
            ->with('path' . DIRECTORY_SEPARATOR . 'scheduled_feed', null, null)
            ->willThrowException(new FileSystemException(new Phrase('An error')));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Could not get PureClarity schedule data: An error');

        $status = $this->object->getFeedStatus('product', 1);

        $this->assertEquals(
            [
                'enabled' => true,
                'running' => false,
                'class' => 'pc-feed-not-sent',
                'label' => 'Not Sent',
            ],
            $status
        );
    }

    public function testGetProgressDataException()
    {
        $this->initRunningFeedsStateObject(1, ['product']);

        $this->readInterface->expects($this->at(3))
            ->method('readFile')
            ->with('progress_filename', null, null)
            ->willThrowException(new FileSystemException(new Phrase('An error')));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Could not get PureClarity feed progress data: An error');

        $status = $this->object->getAreFeedsInProgress(['product'], 1);
        $this->assertEquals(true, $status);
    }
}
