<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Cron;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Cron\RunNightly;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed\Runner;
use Psr\Log\LoggerInterface;

/**
 * Class RunNightlyTest
 *
 * Tests the methods in \Pureclarity\Core\Cron\RunNightly
 */
class RunNightlyTest extends TestCase
{
    /** @var MockObject|RunNightly $object */
    private $object;

    /** @var MockObject|StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    /** @var MockObject|CoreConfig $coreConfig */
    private $coreConfig;

    /** @var MockObject|Runner $cron */
    private $cron;

    /**
     * Sets up RunNightly with dependencies
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->cron = $this->createMock(Runner::class);

        $this->object = new RunNightly(
            $this->storeManager,
            $this->logger,
            $this->coreConfig,
            $this->cron
        );
    }

    /**
     * @param int[] $storeIds
     */
    private function setupGetStores(array $storeIds)
    {
        $stores = [];
        foreach ($storeIds as $storeId) {
            $store = $this->createMock(StoreInterface::class);

            $store->method('getId')
                ->willReturn($storeId);

            $stores[$storeId] = $store;
        }

        $this->storeManager->expects(self::at(0))
            ->method('getStores')
            ->willReturn($stores);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(RunNightly::class, $this->object);
    }

    /**
     * Tests how execute handles a single store setup with no signup request present
     */
    public function testSingleStoreNotEnabled()
    {
        $this->setupGetStores([1]);

        $this->coreConfig->expects(self::once())
            ->method('isDailyFeedActive')
            ->with(1)
            ->willReturn(false);

        $this->cron->expects(self::never())
            ->method('allFeeds');

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with an incomplete signup request present
     */
    public function testSingleStoreEnabled()
    {
        $this->setupGetStores([1]);

        $this->coreConfig->expects(self::once())
            ->method('isDailyFeedActive')
            ->with(1)
            ->willReturn(true);

        $this->cron->expects(self::once())
            ->method('allFeeds')
            ->with(1);

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with an incomplete signup request present
     */
    public function testMultiStoreNoneEnabled()
    {
        $this->setupGetStores([1,17,42]);

        $this->coreConfig->expects(self::at(0))
            ->method('isDailyFeedActive')
            ->with(1)
            ->willReturn(false);

        $this->coreConfig->expects(self::at(1))
            ->method('isDailyFeedActive')
            ->with(17)
            ->willReturn(false);

        $this->coreConfig->expects(self::at(2))
            ->method('isDailyFeedActive')
            ->with(42)
            ->willReturn(false);

        $this->cron->expects(self::never())
            ->method('allFeeds');

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with an error in checkStatus
     */
    public function testMultiStoreOneEnabled()
    {
        $this->setupGetStores([1,17,42]);

        $this->coreConfig->expects(self::at(0))
            ->method('isDailyFeedActive')
            ->with(1)
            ->willReturn(false);

        $this->coreConfig->expects(self::at(1))
            ->method('isDailyFeedActive')
            ->with(17)
            ->willReturn(true);

        $this->coreConfig->expects(self::at(2))
            ->method('isDailyFeedActive')
            ->with(42)
            ->willReturn(false);

        $this->cron->expects(self::once())
            ->method('allFeeds')
            ->with(17);

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with an error in checkStatus
     */
    public function testMultiStoreAllEnabled()
    {
        $this->setupGetStores([1,17,42]);

        $this->coreConfig->expects(self::at(0))
            ->method('isDailyFeedActive')
            ->with(1)
            ->willReturn(true);

        $this->coreConfig->expects(self::at(1))
            ->method('isDailyFeedActive')
            ->with(17)
            ->willReturn(true);

        $this->coreConfig->expects(self::at(2))
            ->method('isDailyFeedActive')
            ->with(42)
            ->willReturn(true);

        $this->cron->expects(self::at(0))
            ->method('allFeeds')
            ->with(1);

        $this->cron->expects(self::at(1))
            ->method('allFeeds')
            ->with(17);

        $this->cron->expects(self::at(2))
            ->method('allFeeds')
            ->with(42);

        $this->object->execute();
    }
}
