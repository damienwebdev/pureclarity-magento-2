<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\State;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\Feed\State\Request;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\State;

/**
 * Class RequestTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Request
 */
class RequestTest extends TestCase
{
    /** @var Request $object */
    private $object;

    /** @var MockObject|StateRepositoryInterface */
    private $stateRepository;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var MockObject|SerializerInterface */
    private $serializer;

    protected function setUp()
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

        $this->serializer->method('serialize')->willReturnCallback(
            function ($param) {
                return '{"product"}';
            }
        );

        $this->serializer->method('unserialize')->willReturnCallback(
            function ($param) {
                return ['product'];
            }
        );

        $this->object = new Request(
            $this->stateRepository,
            $this->logger,
            $this->serializer
        );
    }

    /**
     * Generates a State mock
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
     * Sets up a default state object to return for given state row
     *
     * @param string $name
     * @param int $storeId
     * @param bool $saveError
     */
    private function initStateObject(string $name, int $storeId, $saveError = false)
    {
        $state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $state->expects(self::once())->method('setStoreId')->with($storeId);
        $state->expects(self::once())->method('setName')->with('requested_feeds');
        $state->expects(self::once())->method('setValue')->with('{"product"}');

        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with($name, $storeId)
            ->willReturn($state);

        if ($saveError) {
            $this->stateRepository->expects(self::once())
                ->method('save')
                ->with($state)
                ->willThrowException(new CouldNotSaveException(new Phrase('An error')));
        } else {
            $this->stateRepository->expects(self::once())
                ->method('save')
                ->with($state);
        }
    }

    public function testInstance()
    {
        self::assertInstanceOf(Request::class, $this->object);
    }

    public function testRequestFeeds()
    {
        $this->initStateObject('requested_feeds', 1, 0);
        $this->object->requestFeeds(1, ['product']);
    }

    public function testRequestFeedsWithSaveError()
    {
        $this->initStateObject('requested_feeds', 1, 1);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not request feeds: An error');

        $this->object->requestFeeds(1, ['product']);
    }

    public function testGetAllRequestedFeedsNoResult()
    {
        $this->stateRepository->expects(self::once())
            ->method('getListByName')
            ->with('requested_feeds')
            ->willReturn([]);

        $requested = $this->object->getAllRequestedFeeds();
        self::assertEquals([], $requested);
    }

    public function testGetAllRequestedFeedsError()
    {
        $this->stateRepository->expects(self::once())
            ->method('getListByName')
            ->with('requested_feeds')
            ->willThrowException(new \Exception('an error'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load requested feeds: an error');

        $requested = $this->object->getAllRequestedFeeds();
        self::assertEquals([], $requested);
    }

    public function testGetAllRequestedFeedsOneStoreNoFeeds()
    {
        $this->stateRepository->expects(self::once())
            ->method('getListByName')
            ->with('requested_feeds')
            ->willReturn([$this->getStateMock()]);

        $requested = $this->object->getAllRequestedFeeds();
        self::assertEquals([], $requested);
    }

    public function testGetAllRequestedFeedsOneStoreWithFeeds()
    {
        $this->stateRepository->expects(self::once())
            ->method('getListByName')
            ->with('requested_feeds')
            ->willReturn([$this->getStateMock(1, 'requested_feeds', 'nonempty', 1)]);

        $requested = $this->object->getAllRequestedFeeds();
        self::assertEquals([1 => ['product']], $requested);
    }

    public function testGetAllRequestedFeedsTwoStoreWithFeeds()
    {
        $this->stateRepository->expects(self::once())
            ->method('getListByName')
            ->with('requested_feeds')
            ->willReturn([
                $this->getStateMock(1, 'requested_feeds', 'nonempty', 1),
                $this->getStateMock(1, 'requested_feeds', 'nonempty', 17),
            ]);

        $requested = $this->object->getAllRequestedFeeds();
        self::assertEquals([1 => ['product'], 17 => ['product'] ], $requested);
    }

    public function testGetStoreRequestedFeedsNoResult()
    {
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('requested_feeds', 1)
            ->willReturn($this->getStateMock(1, 'requested_feeds', '', 1));

        $requested = $this->object->getStoreRequestedFeeds(1);
        self::assertEquals([], $requested);
    }

    public function testGetStoreRequestedFeedsError()
    {
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('requested_feeds', 1)
            ->willThrowException(new \Exception('an error'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load requested feeds for store 1: an error');

        $requested = $this->object->getStoreRequestedFeeds(1);
        self::assertEquals([], $requested);
    }

    public function testGetStoreRequestedFeedsWithFeeds()
    {
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore', 1)
            ->with('requested_feeds')
            ->willReturn($this->getStateMock(1, 'requested_feeds', 'nonempty', 1));

        $requested = $this->object->getStoreRequestedFeeds(1);
        self::assertEquals(['product'], $requested);
    }

    public function testDeleteRequestedFeeds()
    {
        $state = $this->getStateMock(1, 'requested_feeds', '', 1);
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('requested_feeds', 1)
            ->willReturn($state);

        $this->stateRepository->expects(self::once())
            ->method('delete')
            ->with($state);

        $this->object->deleteRequestedFeeds(1);
    }

    public function testDeleteRequestedFeedsNoValue()
    {
        $state = $this->getStateMock();
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('requested_feeds', 1)
            ->willReturn($state);

        $this->stateRepository->expects(self::never())
            ->method('delete');

        $this->object->deleteRequestedFeeds(1);
    }

    public function testDeleteRequestedFeedsError()
    {
        $state = $this->getStateMock(1, 'requested_feeds', '', 1);
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('requested_feeds', 1)
            ->willReturn($state);

        $this->stateRepository->expects(self::once())
            ->method('delete')
            ->with($state)
            ->willThrowException(new CouldNotDeleteException(new Phrase('An error')));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not delete requested feeds for store 1: An error');

        $this->object->deleteRequestedFeeds(1);
    }
}
