<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Cron;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Cron\RunRequestedFeeds;
use Magento\Store\Api\Data\StoreInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Cron;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\Request;

/**
 * Class RunScheduledTest
 *
 * Tests the methods in \Pureclarity\Core\Cron\RunScheduled
 */
class RunRequestedFeedsTest extends TestCase
{
    /** @var MockObject|RunRequestedFeeds $object */
    private $object;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    /** @var MockObject|CoreConfig $coreConfig */
    private $coreConfig;

    /** @var MockObject|Cron $feedRunner */
    private $feedRunner;

    /** @var MockObject|Request $feedRequest */
    private $feedRequest;

    /**
     * Sets up RunScheduled with dependencies
     */
    protected function setUp()
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedRunner = $this->getMockBuilder(Cron::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedRequest = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new RunRequestedFeeds(
            $this->logger,
            $this->coreConfig,
            $this->feedRunner,
            $this->feedRequest
        );
    }

    /**
     * @param int[] $storeIds
     */
    private function setupTestData(array $storeIds)
    {
        $stores = [];

        $index = 0;

        foreach ($storeIds as $storeId => $active) {
            $stores[$storeId] = ['product'];
            $this->coreConfig->expects(self::at($index))->method('isActive')->with($storeId)->willReturn($active);
            $index++;
        }

        $this->feedRequest->expects(self::once())->method('getAllRequestedFeeds')->willReturn($stores);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(RunRequestedFeeds::class, $this->object);
    }

    /**
     * Tests how execute handles a single store setup with no signup request present
     */
    public function testSingleStoreNotEnabled()
    {
        $this->setupTestData([1 => false]);

        $this->feedRequest->expects(self::never())->method('deleteRequestedFeeds');
        $this->feedRunner->expects(self::never())->method('selectedFeeds');

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with an incomplete signup request present
     */
    public function testSingleStoreEnabled()
    {
        $this->setupTestData([1 => true]);

        $this->feedRequest->expects(self::once())->method('deleteRequestedFeeds')->with(1);
        $this->feedRunner->expects(self::once())->method('selectedFeeds')->with(1, ['product']);

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with an incomplete signup request present
     */
    public function testMultiStoreNoneEnabled()
    {
        $this->setupTestData([
            1 => false,
            17 => false,
            42 => false
        ]);

        $this->feedRequest->expects(self::never())->method('deleteRequestedFeeds');
        $this->feedRunner->expects(self::never())->method('selectedFeeds');

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with an error in checkStatus
     */
    public function testMultiStoreOneEnabled()
    {
        $this->setupTestData([
            1 => false,
            17 => true,
            42 => false
        ]);

        $this->feedRequest->expects(self::once())->method('deleteRequestedFeeds')->with(17);
        $this->feedRunner->expects(self::once())->method('selectedFeeds')->with(17, ['product']);

        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with an error in checkStatus
     */
    public function testMultiStoreAllEnabled()
    {
        $this->setupTestData([
            1 => true,
            17 => true,
            42 => true
        ]);

        $this->feedRequest->expects(self::at(1))->method('deleteRequestedFeeds')->with(1);
        $this->feedRunner->expects(self::at(0))->method('selectedFeeds')->with(1, ['product']);

        $this->feedRequest->expects(self::at(2))->method('deleteRequestedFeeds')->with(17);
        $this->feedRunner->expects(self::at(1))->method('selectedFeeds')->with(17, ['product']);

        $this->feedRequest->expects(self::at(3))->method('deleteRequestedFeeds')->with(42);
        $this->feedRunner->expects(self::at(2))->method('selectedFeeds')->with(42, ['product']);

        $this->object->execute();
    }
}
