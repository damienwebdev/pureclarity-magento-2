<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\State;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\State\RunDate;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Pureclarity\Core\Model\State;

/**
 * Class RunDateTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\RunDate
 */
class RunDateTest extends TestCase
{
    /** @var RunDate $object */
    private $object;

    /** @var MockObject|StateRepositoryInterface */
    private $stateRepository;

    /** @var MockObject|LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new RunDate(
            $this->stateRepository,
            $this->logger
        );
    }

    /**
     * Generates a State mock
     * @param int|null $id
     * @param string|null $name
     * @param string|null $value
     * @param int|null $storeId
     * @return State|MockObject
     */
    private function getStateMock(
        int $id = null,
        string $name = null,
        string $value = null,
        int $storeId = null
    ) {
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
     * Sets up a default state object to return for given state row
     *
     * @param int $id
     * @param string $name
     * @param int $storeId
     * @param string $value
     * @return State|MockObject
     */
    private function initStateRepositoryLoad(int $id, string $name, int $storeId, string $value)
    {
        $state = $this->getStateMock($id, $name, $value, $storeId);

        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with($name, $storeId)
            ->willReturn($state);

        return $state;
    }

    /**
     * Sets up a default state object to return for given state row
     *
     * @param string $name
     * @param int $storeId
     * @param string $value
     * @param string $saveError
     */
    private function initStateObjectWithSave(
        string $name,
        int $storeId,
        string $value,
        string $saveError = ''
    ): void {
        $state = $this->initStateRepositoryLoad(1, $name, $storeId, $value);

        $state->expects(self::once())
            ->method('setStoreId')
            ->with($storeId);

        $state->expects(self::once())
            ->method('setName')
            ->with($name);

        $state->expects(self::once())
            ->method('setValue')
            ->with($value);

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
        self::assertInstanceOf(RunDate::class, $this->object);
    }

    /**
     * Test that getLastRunDate with no data is handled correctly
     */
    public function testGetLastRunDateNoData(): void
    {
        $this->initStateRepositoryLoad(1, 'last_product_feed_date', 1, '');
        $runningFeeds = $this->object->getLastRunDate(1, 'product');
        self::assertEquals('', $runningFeeds);
    }

    /**
     * Test that getLastRunDate with data is returned as expected
     */
    public function testGetLastRunDateWithData(): void
    {
        $this->initStateRepositoryLoad(1, 'last_product_feed_date', 1, 'something');
        $runDate = $this->object->getLastRunDate(1, 'product');
        self::assertEquals('something', $runDate);
    }

    /**
     * Test that setLastRunDate saves data given to it
     */
    public function testSetLastRunDate(): void
    {
        $date = date('Y-m-d H:i:s');
        $this->initStateObjectWithSave('last_product_feed_date', 1, $date);
        $this->object->setLastRunDate(1, 'product', $date);
    }

    /**
     * Test that setLastRunDate handles exceptions by logging an error
     */
    public function testSetLastRunDateException(): void
    {
        $date = date('Y-m-d H:i:s');
        $this->initStateObjectWithSave('last_product_feed_date', 1, $date, 'An error');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not save last updated date: An error');

        $this->object->setLastRunDate(1, 'product', $date);
    }
}
