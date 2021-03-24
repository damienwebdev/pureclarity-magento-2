<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Runner;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\FeedFactory;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed\State\Running;
use Pureclarity\Core\Model\Feed\State\RunDate;
use Pureclarity\Core\Model\Feed\State\Progress;
use Pureclarity\Core\Model\Feed\State\Error;
use Pureclarity\Core\Model\Feed\TypeHandler;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use PureClarity\Api\Feed\Feed;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;

/**
 * Class RunnerTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Runner
 */
class RunnerTest extends TestCase
{
    /** @var string */
    private const CONFIG_ACCESS_KEY = 'AccessKey1';
    /** @var string */
    private const CONFIG_SECRET_KEY = 'SecretKey123';
    /** @var string */
    private const CONFIG_REGION = '1';
    /** @var int */
    private const STORE_ID = 1;

    /** @var Runner */
    private $object;

    /** @var MockObject|Data */
    private $coreHelper;

    /** @var MockObject|FeedFactory */
    private $coreFeedFactory;

    /** @var MockObject|StateRepositoryInterface */
    private $stateRepository;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|Running */
    private $runningFeeds;

    /** @var MockObject|RunDate */
    private $feedRunDate;

    /** @var MockObject|Progress */
    private $feedProgress;

    /** @var MockObject|Error */
    private $feedError;

    /** @var MockObject|TypeHandler */
    private $feedTypeHandler;

    protected function setUp(): void
    {
        $this->coreHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreFeedFactory = $this->getMockBuilder(FeedFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateRepository = $this->getMockBuilder(StateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->runningFeeds = $this->getMockBuilder(Running::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedRunDate = $this->getMockBuilder(RunDate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedProgress = $this->getMockBuilder(Progress::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedError = $this->getMockBuilder(Error::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedTypeHandler = $this->getMockBuilder(TypeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Runner(
            $this->coreHelper,
            $this->coreFeedFactory,
            $this->stateRepository,
            $this->logger,
            $this->coreConfig,
            $this->runningFeeds,
            $this->feedRunDate,
            $this->feedProgress,
            $this->feedError,
            $this->feedTypeHandler
        );
    }

    /**
     * Sets up config value mocks
     */
    public function setupConfig(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('getAccessKey')
            ->willReturn(self::CONFIG_ACCESS_KEY);

        $this->coreConfig->expects(self::once())
            ->method('getSecretKey')
            ->willReturn(self::CONFIG_SECRET_KEY);

        $this->coreConfig->expects(self::once())
            ->method('getRegion')
            ->willReturn(self::CONFIG_REGION);
    }

    /**
     * Sets up feed handler mock
     * @param string $type
     * @param int $numPages
     * @param int $pageSize
     * @param string $error
     */
    public function setupFeedHandler(string $type, int $numPages, int $pageSize, string $error = ''): void
    {
        $feedDataHandler = $this->getMockBuilder(FeedDataManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feedDataHandler->expects(self::once())
            ->method('getTotalPages')
            ->willReturn($numPages);

        $feedHandler = $this->getMockBuilder(FeedManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feedHandler->expects(self::once())
            ->method('isEnabled')
            ->with(self::STORE_ID)
            ->willReturn(true);

        $feedHandler->expects(self::once())
            ->method('getFeedDataHandler')
            ->willReturn($feedDataHandler);

        $this->feedTypeHandler->method('getFeedHandler')
            ->with($type)
            ->willReturn($feedHandler);

        if ($numPages > 0) {
            $feedBuilder = $this->getMockBuilder(Feed::class)
                ->disableOriginalConstructor()
                ->getMock();

            $feedHandler->expects(self::once())
                ->method('getFeedBuilder')
                ->with(
                    self::CONFIG_ACCESS_KEY,
                    self::CONFIG_SECRET_KEY,
                    self::CONFIG_REGION
                )
                ->willReturn($feedBuilder);

            if ($error) {
                $feedBuilder->expects(self::once())
                    ->method('start')
                    ->willThrowException(new \Exception($error));
            } else {
                $feedBuilder->expects(self::once())
                    ->method('start')
                    ->willReturn($feedDataHandler);

                $feedRowDataHandler = $this->getMockBuilder(FeedRowDataManagementInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $feedHandler->expects(self::once())
                    ->method('getRowDataHandler')
                    ->willReturn($feedRowDataHandler);

                for ($page = 1; $page <= $numPages; $page++) {
                    $itemData = [];
                    for ($item = 1; $item <= $pageSize; $item++) {
                        $itemData[] = [$item];
                    }

                    $feedDataHandler->expects(self::at($page))
                        ->method('getPageData')
                        ->with(self::STORE_ID, $page)
                        ->willReturn($itemData);

                    foreach ($itemData as $x => $item) {
                        $feedBuilder->expects(self::at($x + 1))
                            ->method('append')
                            ->willReturn($item);

                        $feedRowDataHandler->expects(self::at($x))
                            ->method('getRowData')
                            ->with(self::STORE_ID, $item)
                            ->willReturn($item);
                    }
                }

                $feedBuilder->expects(self::once())
                    ->method('end')
                    ->willReturn($feedDataHandler);
            }
        }
    }

    /**
     * Sets up feed handler mock for a disabled feed
     * @param string $type
     */
    public function setupFeedHandlerDisabled(string $type): void
    {
        $feedHandler = $this->getMockBuilder(FeedManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feedHandler->expects(self::once())
            ->method('isEnabled')
            ->with(self::STORE_ID)
            ->willReturn(false);

        $feedHandler->expects(self::never())
            ->method('getFeedDataHandler');

        $feedHandler->expects(self::never())
            ->method('getRowDataHandler');

        $feedHandler->expects(self::never())
            ->method('getFeedBuilder');

        $this->feedTypeHandler->method('getFeedHandler')
            ->with($type)
            ->willReturn($feedHandler);
    }

    /**
     * Sets up feed progress
     *
     * @param string $type
     * @param array $progressPoints
     */
    public function setupFeedProgress(string $type, array $progressPoints): void
    {
        foreach ($progressPoints as $index => $progress) {
            $this->feedProgress->expects(self::at($index))
                ->method('updateProgress')
                ->with(self::STORE_ID, $type, (string)$progress);
        }
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Runner::class, $this->object);
    }

    /**
     * Tests that a disabled feed doesnt gets sent
     */
    public function testSendDisabledFeed(): void
    {
        $this->setupFeedHandlerDisabled(Feed::FEED_TYPE_BRAND);
        $this->object->sendFeed(self::STORE_ID, Feed::FEED_TYPE_BRAND);
    }

    /**
     * Tests that the user feed gets sent
     */
    public function testSendUserFeed(): void
    {
        $this->setupConfig();
        $this->setupFeedHandler(Feed::FEED_TYPE_USER, 2, 2);
        $this->setupFeedProgress(Feed::FEED_TYPE_USER, [0,50,100]);
        $this->object->sendFeed(self::STORE_ID, Feed::FEED_TYPE_USER);
    }

    /**
     * Tests that the user feed doesnt send when no users present
     */
    public function testSendFeedException(): void
    {
        $this->setupConfig();
        $this->setupFeedHandler(Feed::FEED_TYPE_USER, 2, 2, 'An Error');
        $this->setupFeedProgress(Feed::FEED_TYPE_USER, [0]);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Error with ' . Feed::FEED_TYPE_USER . ' feed: An Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, Feed::FEED_TYPE_USER, 'An Error');

        $this->object->sendFeed(self::STORE_ID, Feed::FEED_TYPE_USER);
    }
}
