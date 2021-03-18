<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\Feed\Progress;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem;
use Pureclarity\Core\Helper\Data;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Pureclarity\Core\Model\State;
use Magento\Framework\Exception\FileSystemException;

/**
 * Class ProgressTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Progress
 */
class ProgressTest extends TestCase
{
    /** @var Progress $object */
    private $object;

    /** @var MockObject|StateRepositoryInterface */
    private $stateRepository;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var MockObject|Filesystem */
    private $fileSystem;

    /** @var MockObject|Data */
    private $coreHelper;

    /** @var MockObject|ReadInterface */
    private $readInterface;

    /** @var MockObject|WriteInterface */
    private $writeInterface;

    protected function setUp()
    {
        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileSystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->readInterface = $this->getMockBuilder(ReadInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->writeInterface = $this->getMockBuilder(WriteInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Progress(
            $this->stateRepository,
            $this->logger,
            $this->fileSystem,
            $this->coreHelper
        );

        $this->setDefaultMockBehaviour();
    }

    /**
     * Sets default behaviours for various mocks used in this test
     */
    private function setDefaultMockBehaviour()
    {
        $this->fileSystem->method('getDirectoryRead')->willReturn($this->readInterface);
        $this->fileSystem->method('getDirectoryWrite')->willReturn($this->writeInterface);
        $this->coreHelper->method('getProgressFileName')->willReturn('progress_filename');
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

        $state->method('getId')->willReturn($id);
        $state->method('getStoreId')->willReturn($storeId);
        $state->method('getName')->willReturn($name);
        $state->method('getValue')->willReturn($value);

        return $state;
    }

    /**
     * Sets up a default state object to return for "last_feed_error" state row
     *
     * @param string $name
     * @param int $index
     * @param int $id
     * @param bool $deleteError
     */
    private function initStateObject(string $name, int $index, $id = 0, $deleteError = false)
    {
        $state = $this->getStateMock($id, $name, '', '0');
        $this->stateRepository->expects(self::at($index))
            ->method('getByNameAndStore')
            ->with($name, 1)
            ->willReturn($state);

        if ($deleteError) {
            $this->stateRepository->expects(self::at($index + 1))
                ->method('delete')
                ->with($state)
                ->willThrowException(new CouldNotDeleteException(new Phrase('An error')));
        } elseif ($id) {
            $this->stateRepository->expects(self::at($index + 1))
                ->method('delete')
                ->with($state);
        }
    }

    public function testInstance()
    {
        self::assertInstanceOf(Progress::class, $this->object);
    }

    public function testResetProgressWithData()
    {
        $this->initStateObject('last_feed_error', 0, 1);
        $this->initStateObject('running_feeds', 2, 1);
        $this->readInterface->method('isExist')->willReturn(true);
        $this->writeInterface->expects(self::once())->method('delete')->with('progress_filename');
        $this->object->resetProgress(1);
    }

    public function testResetProgressWithoutData()
    {
        $this->initStateObject('last_feed_error', 0);
        $this->initStateObject('running_feeds', 1);
        $this->readInterface->method('isExist')->willReturn(false);
        $this->writeInterface->expects(self::never())->method('delete');
        $this->object->resetProgress(1);
    }

    public function testResetProgressWithFeedErrorException()
    {
        $this->initStateObject('last_feed_error', 0, 1, true);
        $this->initStateObject('running_feeds', 2, 1);
        $this->readInterface->method('isExist')->willReturn(true);
        $this->writeInterface->expects(self::once())->method('delete')->with('progress_filename');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not clear last feed error: An error');

        $this->object->resetProgress(1);
    }

    public function testResetProgressWithRunningFeedsException()
    {
        $this->initStateObject('last_feed_error', 0, 1);
        $this->initStateObject('running_feeds', 2, 1, true);
        $this->readInterface->method('isExist')->willReturn(true);
        $this->writeInterface->expects(self::once())->method('delete')->with('progress_filename');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not clear running feeds: An error');

        $this->object->resetProgress(1);
    }

    public function testResetProgressWithFileException()
    {
        $this->initStateObject('last_feed_error', 0, 1);
        $this->initStateObject('running_feeds', 2, 1);

        $this->readInterface->method('isExist')->willReturn(true);
        $this->writeInterface->expects(self::once())
            ->method('delete')
            ->with('progress_filename')
            ->willThrowException(new FileSystemException(new Phrase('An error')));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Error deleting progress file: An error');

        $this->object->resetProgress(1);
    }
}
