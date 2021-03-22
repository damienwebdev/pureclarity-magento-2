<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Error;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Pureclarity\Core\Model\State;

/**
 * Class ErrorTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Error
 */
class ErrorTest extends TestCase
{
    /** @var Error $object */
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

        $this->object = new Error(
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

        $state->method('getId')->willReturn($id);
        $state->method('getStoreId')->willReturn($storeId);
        $state->method('getName')->willReturn($name);
        $state->method('getValue')->willReturn($value);

        return $state;
    }

    /**
     *  Sets up interaction with the state repository, for a state row save
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
     * @param string $value
     * @param string $saveError
     */
    private function initStateRepositorySave(
        string $name,
        int $storeId,
        string $value,
        string $saveError = ''
    ): void {
        $state = $this->initStateRepositoryLoad(1, $name, $storeId, $value);
        $state->expects(self::once())->method('setStoreId')->with($storeId);
        $state->expects(self::once())->method('setName')->with($name);
        $state->expects(self::once())->method('setValue')->with($value);

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
     * Tests class gets set up correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Error::class, $this->object);
    }

    /**
     * Tests getFeedError returns no error correctly
     */
    public function testGetFeedErrorNoData(): void
    {
        $this->initStateRepositoryLoad(1, 'last_product_feed_error', 1, '');
        $error = $this->object->getFeedError(1, 'product');
        self::assertEquals('', $error);
    }

    /**
     * Tests getFeedError returns an error correctly
     */
    public function testGetFeedErrorWithData(): void
    {
        $this->initStateRepositoryLoad(1, 'last_product_feed_error', 1, 'something');
        $error = $this->object->getFeedError(1, 'product');
        self::assertEquals('something', $error);
    }

    /**
     * Tests saveFeedError saves correctly
     */
    public function testSaveFeedError(): void
    {
        $this->initStateRepositorySave('last_product_feed_error', 1, 'A feed Error');
        $this->object->saveFeedError(1, 'product', 'A feed Error');
    }

    /**
     * Tests saveFeedError handles exceptions correctly
     */
    public function testSaveFeedErrorException(): void
    {
        $this->initStateRepositorySave('last_product_feed_error', 1, 'A feed Error', 'An error');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not save feed error: An error');

        $this->object->saveFeedError(1, 'product', 'A feed Error');
    }
}
