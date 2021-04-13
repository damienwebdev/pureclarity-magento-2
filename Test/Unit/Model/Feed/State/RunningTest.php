<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\State;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\State\Running;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Pureclarity\Core\Model\State;

/**
 * Class RunningTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Running
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RunningTest extends TestCase
{
    /** @var Running $object */
    private $object;

    /** @var MockObject|StateRepositoryInterface */
    private $stateRepository;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var MockObject|SerializerInterface */
    private $serializer;

    protected function setUp(): void
    {
        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Running(
            $this->stateRepository,
            $this->logger,
            $this->serializer
        );
    }

    /**
     * Generates a State mock
     * @param int|null $id
     * @param string|null $name
     * @param string|null $value
     * @param int|null $storeId
     * @return MockObject
     */
    private function getStateMock(
        int $id = null,
        string $name = null,
        string $value = null,
        int $storeId = null
    ): MockObject {
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

    /**
     * Sets up interaction with the state repository, for a state row load
     *
     * @param int $id
     * @param string $name
     * @param int $storeId
     * @param string $value
     * @return MockObject
     */
    private function initStateRepositoryLoad(int $id, string $name, int $storeId, string $value): MockObject
    {
        $state = $this->getStateMock($id, $name, $value, $storeId);

        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with($name, $storeId)
            ->willReturn($state);

        return $state;
    }

    /**
     * Sets up interaction with the state repository, for a state row save
     *
     * @param string $name
     * @param int $storeId
     * @param array $feeds
     * @param string $saveMode
     * @param string $saveError
     */
    private function initStateRepositorySave(
        string $name,
        int $storeId,
        array $feeds,
        string $saveMode = 'full',
        string $saveError = ''
    ): void {
        $state = $this->initStateRepositoryLoad(1, $name, $storeId, 'something');

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with($feeds)
            ->willReturn('{"product", "category"}');

        if ($saveMode === 'full') {
            $state->expects(self::once())
                ->method('setStoreId')
                ->with($storeId);

            $state->expects(self::once())
                ->method('setName')
                ->with($name);
        }

        $state->expects(self::once())->method('setValue')->with('{"product", "category"}');

        if ($saveError) {
            $this->stateRepository->expects(self::once())
                ->method('save')
                ->with($state)
                ->willThrowException(new CouldNotSaveException(new Phrase($saveError)));
        } else {
            $this->stateRepository->expects(self::once())
                ->method('save')
                ->with($state);
        }
    }

    /**
     * Test that class is set up correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Running::class, $this->object);
    }

    /**
     * Test that getRunningFeeds with no data is handled correctly
     */
    public function testGetRunningFeedsNoData(): void
    {
        $this->initStateRepositoryLoad(1, 'running_feeds', 1, '');

        $this->serializer->expects(self::never())
            ->method('unserialize');

        $runningFeeds = $this->object->getRunningFeeds(1);
        self::assertEquals([], $runningFeeds);
    }

    /**
     * Test that getRunningFeeds with data is returned as expected
     */
    public function testGetRunningFeedsWithData(): void
    {
        $this->initStateRepositoryLoad(1, 'running_feeds', 1, 'something');

        $this->serializer->expects(self::once())->method('unserialize')
            ->with('something')
            ->willReturn(['product', 'category']);

        $runningFeeds = $this->object->getRunningFeeds(1);
        self::assertEquals(['product', 'category'], $runningFeeds);
    }

    /**
     * Test that setRunningFeeds saves data given to it
     */
    public function testSetRunningFeeds(): void
    {
        $this->initStateRepositorySave('running_feeds', 1, ['product', 'category']);
        $this->object->setRunningFeeds(1, ['product', 'category']);
    }

    /**
     * Test that setRunningFeeds handles exceptions by logging an error
     */
    public function testSetRunningFeedsException(): void
    {
        $this->initStateRepositorySave('running_feeds', 1, ['product', 'category'], 'full', 'An error');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not save running feed data: An error');

        $this->object->setRunningFeeds(1, ['product', 'category']);
    }

    /**
     * Test that removeRunningFeed removes the provided feed from existing data correctly
     */
    public function testRemoveRunningFeeds(): void
    {
        $this->initStateRepositorySave('running_feeds', 1, ['category'], 'simple');

        $this->serializer->expects(self::once())->method('unserialize')
            ->with('something')
            ->willReturn(['product', 'category']);

        $this->object->removeRunningFeed(1, 'product');
    }

    /**
     * Test that removeRunningFeed doesnt remove the provided feed, if it's not present
     */
    public function testRemoveRunningFeedsNoRemove(): void
    {
        $this->initStateRepositorySave('running_feeds', 1, ['category'], 'simple');

        $this->serializer->expects(self::once())->method('unserialize')
            ->with('something')
            ->willReturn(['category']);

        $this->object->removeRunningFeed(1, 'product');
    }

    /**
     * Test that removeRunningFeed handles exceptions by logging an error
     */
    public function testRemoveRunningFeedsException(): void
    {
        $this->initStateRepositorySave('running_feeds', 1, ['category'], 'simple', 'An error');

        $this->serializer->expects(self::once())->method('unserialize')
            ->with('something')
            ->willReturn(['product', 'category']);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not remove running feed: An error');

        $this->object->removeRunningFeed(1, 'product');
    }

    /**
     * Test that deleteRunningFeeds deletes an existing state row as expected
     */
    public function testDeleteRunningFeeds(): void
    {
        $state = $this->initStateRepositoryLoad(1, 'running_feeds', 1, 'something');

        $this->stateRepository->expects(self::once())
            ->method('delete')
            ->with($state);

        $this->object->deleteRunningFeeds(1);
    }

    /**
     * Test that deleteRunningFeeds does no delete if no row found
     */
    public function testDeleteRunningFeedsNoDelete(): void
    {
        $this->initStateRepositoryLoad(0, 'running_feeds', 1, 'something');

        $this->stateRepository->expects(self::never())
            ->method('delete');

        $this->object->deleteRunningFeeds(1);
    }

    /**
     * Test that deleteRunningFeeds handles exceptions by logging an error
     */
    public function testDeleteRunningFeedsException(): void
    {
        $state = $this->initStateRepositoryLoad(1, 'running_feeds', 1, 'something');

        $this->stateRepository->expects(self::once())
            ->method('delete')
            ->with($state)
            ->willThrowException(new CouldNotDeleteException(new Phrase('An error')));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not clear running feeds: An error');

        $this->object->deleteRunningFeeds(1);
    }
}
