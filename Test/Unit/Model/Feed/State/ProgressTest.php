<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\State;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\Feed\State\Progress;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\State;

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

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->stateRepository = $this->createMock(StateRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->object = new Progress(
            $this->stateRepository,
            $this->logger
        );
    }

    /**
     * @param int|null $stateId
     * @param string|null $name
     * @param string|null $value
     * @param int|null $storeId
     * @return MockObject
     * @throws \ReflectionException
     */
    private function getStateMock(int $stateId, string $name, string $value, int $storeId): MockObject
    {
        $state = $this->createMock(State::class);
        $state->method('getId')->willReturn($stateId);
        $state->method('getStoreId')->willReturn($storeId);
        $state->method('getName')->willReturn($name);
        $state->method('getValue')->willReturn($value);

        return $state;
    }

    /**
     * Sets up a default state object to return for "last_feed_error" state row
     *
     * @param int $stateId
     * @param string $name
     * @param string $value
     * @param int $storeId
     * @param int $index
     * @return MockObject
     * @throws \ReflectionException
     */
    private function initStateObjectNoSave(
        int $stateId,
        string $name,
        string $value,
        int $storeId,
        int $index
    ): MockObject {
        $state = $this->getStateMock($stateId, $name, $value, $storeId);
        $this->stateRepository->expects(self::at($index))
            ->method('getByNameAndStore')
            ->with($name, $storeId)
            ->willReturn($state);
        return $state;
    }

    /**
     * Sets up a default state object to return for "last_feed_error" state row
     *
     * @param int $stateId
     * @param string $name
     * @param string $value
     * @param int $storeId
     * @param int $index
     * @param bool $saveError
     * @throws \ReflectionException
     */
    private function initStateObjectWithSave(
        int $stateId,
        string $name,
        string $value,
        int $storeId,
        int $index,
        bool $saveError = false
    ): void {
        $state = $this->initStateObjectNoSave($stateId, $name, $value, $storeId, $index);
        if ($saveError) {
            $this->stateRepository->expects(self::at($index + 1))
                ->method('save')
                ->with($state)
                ->willThrowException(new CouldNotSaveException(new Phrase('An error')));
        } elseif ($stateId) {
            $this->stateRepository->expects(self::at($index + 1))
                ->method('save')
                ->with($state);
        }
    }

    /**
     * Test class is set up correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Progress::class, $this->object);
    }

    /**
     * Tests that getProgress returns an empty value when expected
     * @throws \ReflectionException
     */
    public function testGetProgressNoData(): void
    {
        $this->initStateObjectNoSave(1, 'feed_product_progress', '', 1, 0);
        self::assertEquals(
            '',
            $this->object->getProgress(1, 'product')
        );
    }

    /**
     * Tests that getProgress returns a value when expected
     * @throws \ReflectionException
     */
    public function testGetProgressWithData(): void
    {
        $this->initStateObjectNoSave(1, 'feed_product_progress', '42', 1, 0);
        $error = $this->object->getProgress(1, 'product');
        self::assertEquals('42', $error);
    }

    /**
     * Tests that updateProgress passes the right values to the save process
     * @throws \ReflectionException
     */
    public function testUpdateProgress(): void
    {
        $this->initStateObjectWithSave(1, 'feed_product_progress', '42', 1, 0);
        $this->object->updateProgress(1, 'product', '42');
    }

    /**
     * Tests that updateProgress passes handles errors correctly
     * @throws \ReflectionException
     */
    public function testUpdateProgressError(): void
    {
        $this->initStateObjectWithSave(1, 'feed_product_progress', '42', 1, 0, true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not save product feed progress: An error');

        $this->object->updateProgress(1, 'product', '17');
    }
}
