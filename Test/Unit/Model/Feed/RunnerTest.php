<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Dashboard\Banner;
use Pureclarity\Core\Model\Feed\Runner;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed\State\Running;
use Pureclarity\Core\Model\Feed\State\RunDate;
use Pureclarity\Core\Model\Feed\State\Progress;
use Pureclarity\Core\Model\Feed\State\Error;
use Pureclarity\Core\Model\Feed\TypeHandler;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use PureClarity\Api\Feed\Feed;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;
use ReflectionException;

/**
 * Class RunnerTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Runner
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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

    /** @var MockObject|StoreManagerInterface */
    private $storeManager;

    /** @var MockObject|Emulation */
    private $appEmulation;

    /** @var MockObject|Banner */
    private $banner;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->runningFeeds = $this->createMock(Running::class);
        $this->feedRunDate = $this->createMock(RunDate::class);
        $this->feedProgress = $this->createMock(Progress::class);
        $this->feedError = $this->createMock(Error::class);
        $this->feedTypeHandler = $this->createMock(TypeHandler::class);
        $this->appEmulation = $this->createMock(Emulation::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->banner = $this->createMock(Banner::class);

        $this->object = new Runner(
            $this->logger,
            $this->coreConfig,
            $this->runningFeeds,
            $this->feedRunDate,
            $this->feedProgress,
            $this->feedError,
            $this->feedTypeHandler,
            $this->storeManager,
            $this->appEmulation,
            $this->banner
        );
    }

    /**
     * Sets up config value mocks
     */
    public function setupConfig(): void
    {
        $this->coreConfig->expects(self::atLeastOnce())
            ->method('getAccessKey')
            ->willReturn(self::CONFIG_ACCESS_KEY);

        $this->coreConfig->expects(self::atLeastOnce())
            ->method('getSecretKey')
            ->willReturn(self::CONFIG_SECRET_KEY);

        $this->coreConfig->expects(self::atLeastOnce())
            ->method('getRegion')
            ->willReturn(self::CONFIG_REGION);
    }

    /**
     * Sets up a StoreInterface and store manager getStore
     * @param bool $error
     * @return StoreInterface|MockObject
     * @throws ReflectionException
     */
    public function setupStore(bool $error = false)
    {
        $store = $this->createMock(StoreInterface::class);

        $store->method('getId')
            ->willReturn(self::STORE_ID);

        $getStore = $this->storeManager->expects(self::once())
            ->method('getStore')
            ->with(self::STORE_ID);

        if ($error) {
            $getStore->willThrowException(
                new NoSuchEntityException(new Phrase('A Store Error'))
            );
        } else {
            $getStore->willReturn($store);
        }

        return $store;
    }

    /**
     * Sets up feed handler mock
     * @param $store
     * @param string $type
     * @param int $numPages
     * @param int $pageSize
     * @param string $error
     * @throws \ReflectionException
     */
    public function setupFeedHandler(
        $store,
        string $type,
        int $numPages,
        int $pageSize,
        string $error = '',
        int $callNum = 0
    ): void {
        $feedDataHandler = $this->createMock(FeedDataManagementInterface::class);

        $feedDataHandler->expects(self::once())
            ->method('getTotalPages')
            ->willReturn($numPages);

        $feedHandler = $this->createMock(FeedManagementInterface::class);

        $feedHandler->expects(self::once())
            ->method('isEnabled')
            ->with(self::STORE_ID)
            ->willReturn(true);

        $feedHandler->expects(self::exactly(2))
            ->method('requiresEmulation')
            ->willReturn($type === Feed::FEED_TYPE_PRODUCT);

        $feedHandler->expects(self::once())
            ->method('getFeedDataHandler')
            ->willReturn($feedDataHandler);

        $this->feedTypeHandler->expects(self::at($callNum))
            ->method('getFeedHandler')
            ->with($type)
            ->willReturn($feedHandler);

        if ($numPages > 0) {
            $feedBuilder = $this->createMock(Feed::class);

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

                $feedRowDataHandler = $this->createMock(FeedRowDataManagementInterface::class);

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
                        ->with($store, $page)
                        ->willReturn($itemData);

                    foreach ($itemData as $x => $item) {
                        $feedBuilder->expects(self::at($x + 1))
                            ->method('append')
                            ->willReturn($item);

                        $feedRowDataHandler->expects(self::at($x))
                            ->method('getRowData')
                            ->with($store, $item)
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
     * Sets up running feeds state handler
     */
    public function setupRunningState($feeds): void
    {
        $this->runningFeeds->expects(self::once())
            ->method('setRunningFeeds')
            ->with(self::STORE_ID, $feeds);

        $index = 1;
        foreach ($feeds as $feed) {
            $this->runningFeeds->expects(self::at($index))
                ->method('removeRunningFeed')
                ->with(self::STORE_ID, $feed);
            $index++;
        }

        $this->runningFeeds->expects(self::once())
            ->method('deleteRunningFeeds')
            ->with(self::STORE_ID);
    }

    /**
     * Sets up run date state handler
     */
    public function setupRunDateState($feeds): void
    {
        foreach ($feeds as $x => $feed) {
            $this->feedRunDate->expects(self::at($x))
                ->method('setLastRunDate')
                ->with(self::STORE_ID, $feed);
        }
    }

    /**
     * Sets up banner cleanup tasks
     */
    public function setupBannerState(): void
    {
        $this->banner->expects(self::once())
            ->method('removeWelcomeBanner')
            ->with(self::STORE_ID);

        $this->runningFeeds->expects(self::once())
            ->method('deleteRunningFeeds')
            ->with(self::STORE_ID);
    }

    /**
     * Sets up feed progress
     *
     * @param array $progressPoints
     */
    public function setupFeedProgress(array $progressPoints): void
    {
        $index = 0;
        foreach ($progressPoints as $type => $points) {
            foreach ($points as $point) {
                $this->feedProgress->expects(self::at($index))
                    ->method('updateProgress')
                    ->with(self::STORE_ID, $type, (string)$point);
                $index++;
            }
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
     * Tests that allFeeds runs nothing when inactive
     */
    public function testAllFeedsInactive(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isActive')
            ->willReturn(false);

        $this->coreConfig->expects(self::never())
            ->method('getAccessKey');

        $this->feedTypeHandler->expects(self::never())
            ->method('getFeedHandler');

        $this->object->allFeeds(self::STORE_ID);
    }

    /**
     * Tests that allFeeds runs expected feeds
     * @throws ReflectionException
     */
    public function testAllFeeds(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isActive')
            ->willReturn(true);

        $feeds = [
            Feed::FEED_TYPE_PRODUCT,
            Feed::FEED_TYPE_CATEGORY,
            Feed::FEED_TYPE_BRAND,
            Feed::FEED_TYPE_USER
        ];
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupFeedHandler($store, Feed::FEED_TYPE_PRODUCT, 2, 2);
        $this->setupFeedHandler($store, Feed::FEED_TYPE_CATEGORY, 2, 2, '', 1);
        $this->setupFeedHandler($store, Feed::FEED_TYPE_BRAND, 2, 2, '', 2);
        $this->setupFeedHandler($store, Feed::FEED_TYPE_USER, 2, 2, '', 3);
        $this->setupFeedProgress([
            Feed::FEED_TYPE_PRODUCT => [0,50,100],
            Feed::FEED_TYPE_CATEGORY => [0,50,100],
            Feed::FEED_TYPE_BRAND => [0,50,100],
            Feed::FEED_TYPE_USER => [0,50,100]
        ]);
        $this->setupRunningState($feeds);
        $this->setupRunDateState($feeds);
        $this->setupBannerState();
        $this->object->allFeeds(self::STORE_ID);
    }

    /**
     * Tests that selectedFeeds runs provided feeds
     * @throws ReflectionException
     */
    public function testSelectedFeedsActive(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isActive')
            ->willReturn(true);

        $feeds = [Feed::FEED_TYPE_PRODUCT, Feed::FEED_TYPE_CATEGORY];
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupFeedHandler($store, Feed::FEED_TYPE_PRODUCT, 2, 2);
        $this->setupFeedHandler($store, Feed::FEED_TYPE_CATEGORY, 2, 2, '', 1);
        $this->setupFeedProgress([
            Feed::FEED_TYPE_PRODUCT => [0,50,100],
            Feed::FEED_TYPE_CATEGORY => [0,50,100]
        ]);
        $this->setupRunningState($feeds);
        $this->setupRunDateState($feeds);
        $this->setupBannerState();
        $this->object->selectedFeeds(self::STORE_ID, $feeds);
    }

    /**
     * Tests that selectedFeeds does nothing if inactive
     */
    public function testSelectedFeedsInactive(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isActive')
            ->willReturn(false);

        $this->coreConfig->expects(self::never())
            ->method('getAccessKey');

        $this->feedTypeHandler->expects(self::never())
            ->method('getFeedHandler');

        $this->object->selectedFeeds(self::STORE_ID, [Feed::FEED_TYPE_PRODUCT, Feed::FEED_TYPE_CATEGORY]);
    }

    /**
     * Tests that doFeeds runs multiple feeds
     * @throws ReflectionException
     */
    public function testDoFeeds(): void
    {
        $feeds = [Feed::FEED_TYPE_PRODUCT, Feed::FEED_TYPE_CATEGORY];
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupFeedHandler($store, Feed::FEED_TYPE_PRODUCT, 2, 2);
        $this->setupFeedHandler($store, Feed::FEED_TYPE_CATEGORY, 2, 2, '', 1);
        $this->setupFeedProgress([
            Feed::FEED_TYPE_PRODUCT => [0,50,100],
            Feed::FEED_TYPE_CATEGORY => [0,50,100]
        ]);
        $this->setupRunningState($feeds);
        $this->setupRunDateState($feeds);
        $this->setupBannerState();
        $this->object->doFeeds($feeds, self::STORE_ID);
    }

    /**
     * Tests that doFeeds handles an invalid feed
     */
    public function testDoFeedsInvalidFeed(): void
    {
        $feeds = ['fish'];

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Invalid feed type requested: fish');

        $this->setupRunningState($feeds);
        $this->setupBannerState();
        $this->object->doFeeds($feeds, self::STORE_ID);
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
     * Tests that the brand feed gets sent
     * @throws ReflectionException
     */
    public function testSendBrandFeed(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupFeedHandler($store, Feed::FEED_TYPE_BRAND, 2, 2);
        $this->setupFeedProgress([Feed::FEED_TYPE_BRAND => [0,50,100]]);
        $this->object->sendFeed(self::STORE_ID, Feed::FEED_TYPE_BRAND);
    }

    /**
     * Tests that the user feed gets sent
     * @throws ReflectionException
     */
    public function testSendUserFeed(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupFeedHandler($store, Feed::FEED_TYPE_USER, 2, 2);
        $this->setupFeedProgress([Feed::FEED_TYPE_USER => [0,50,100]]);
        $this->object->sendFeed(self::STORE_ID, Feed::FEED_TYPE_USER);
    }

    /**
     * Tests that the product feed gets sent
     * @throws ReflectionException
     */
    public function testSendProductFeed(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupFeedHandler($store, Feed::FEED_TYPE_PRODUCT, 2, 2);
        $this->setupFeedProgress([Feed::FEED_TYPE_PRODUCT => [0,50,100]]);

        $this->appEmulation->expects(self::once())
            ->method('startEnvironmentEmulation')
            ->with(self::STORE_ID, Area::AREA_FRONTEND, true);

        $this->appEmulation->expects(self::once())
            ->method('stopEnvironmentEmulation');

        $this->object->sendFeed(self::STORE_ID, Feed::FEED_TYPE_PRODUCT);
    }

    /**
     * Tests that the user feed gets sent - and that app emulation stops if an exception happens
     * @throws ReflectionException
     */
    public function testSendProductFeedException(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupFeedHandler($store, Feed::FEED_TYPE_PRODUCT, 2, 2, 'An error');
        $this->setupFeedProgress([Feed::FEED_TYPE_PRODUCT => [0]]);

        $this->appEmulation->expects(self::once())
            ->method('startEnvironmentEmulation')
            ->with(self::STORE_ID, Area::AREA_FRONTEND, true);

        $this->appEmulation->expects(self::once())
            ->method('stopEnvironmentEmulation');

        $this->object->sendFeed(self::STORE_ID, Feed::FEED_TYPE_PRODUCT);
    }

    /**
     * Tests that the product feed gets sent
     * @throws ReflectionException
     */
    public function testSendFeedZeroPages(): void
    {
        $store = $this->setupStore();
        $this->setupFeedHandler($store, Feed::FEED_TYPE_PRODUCT, 0, 0);

        $this->logger->expects(self::at(1))
            ->method('debug')
            ->with('No product Feed pages to process');

        $this->object->sendFeed(self::STORE_ID, Feed::FEED_TYPE_PRODUCT);
    }

    /**
     * Tests that the user feed doesnt send when no users present
     * @throws ReflectionException
     */
    public function testSendFeedException(): void
    {
        $store = $this->setupStore();
        $this->setupConfig();
        $this->setupFeedHandler($store, Feed::FEED_TYPE_USER, 2, 2, 'An Error');
        $this->setupFeedProgress([Feed::FEED_TYPE_USER => [0]]);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Error with ' . Feed::FEED_TYPE_USER . ' feed: An Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, Feed::FEED_TYPE_USER, 'An Error');

        $this->object->sendFeed(self::STORE_ID, Feed::FEED_TYPE_USER);
    }

    /**
     * Tests that the user feed doesnt when a store exception happens
     * @throws \ReflectionException
     */
    public function testSendFeedStoreException(): void
    {
        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Error with ' . Feed::FEED_TYPE_USER . ' feed: A Store Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, Feed::FEED_TYPE_USER, 'A Store Error');

        $feedHandler = $this->createMock(FeedManagementInterface::class);

        $feedHandler->expects(self::once())
            ->method('isEnabled')
            ->with(self::STORE_ID)
            ->willReturn(true);

        $this->feedTypeHandler->method('getFeedHandler')
            ->with(Feed::FEED_TYPE_USER)
            ->willReturn($feedHandler);

        $feedHandler->expects(self::never())
            ->method('getFeedDataHandler');

        $this->setupStore(true);

        $this->object->sendFeed(self::STORE_ID, Feed::FEED_TYPE_USER);
    }
}
