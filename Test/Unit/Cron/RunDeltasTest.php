<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Cron;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Cron\RunDeltas;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Delta\Runner;

/**
 * Class RunDeltasTest
 *
 * Tests the methods in \Pureclarity\Core\Cron\RunDeltas
 */
class RunDeltasTest extends TestCase
{
    /** @var MockObject|RunDeltas $object */
    private $object;

    /** @var MockObject|Runner $cron */
    private $runner;

    /** @var StoreManagerInterface|MockObject */
    private $storeManager;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /**
     * Sets up RunDeltas with dependencies
     */
    protected function setUp() : void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->runner = $this->createMock(Runner::class);

        $this->object = new RunDeltas(
            $this->storeManager,
            $this->coreConfig,
            $this->runner
        );
    }

    /**
     * @param int[] $storeIds
     */
    private function setupStoreData(array $storeIds): void
    {
        $stores = [];
        $coreConfigIndex = 0;
        $runnerIndex = 0;
        foreach ($storeIds as $storeId => $deltasEnabled) {
            $store = $this->createMock(StoreInterface::class);

            $store->method('getId')
                ->willReturn($storeId);

            $stores[$storeId] = $store;

            $this->coreConfig->expects(self::at($coreConfigIndex))
                ->method('areDeltasEnabled')
                ->with($storeId)
                ->willReturn($deltasEnabled);

            if ($deltasEnabled) {
                $this->runner->expects(self::at($runnerIndex))
                    ->method('runDeltas')
                    ->with($storeId);
                $runnerIndex++;
            }
            $coreConfigIndex++;
        }

        if ($runnerIndex === 0) {
            $this->runner->expects(self::never())
                ->method('runDeltas');
        }

        $this->storeManager->expects(self::at(0))
            ->method('getStores')
            ->willReturn($stores);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(RunDeltas::class, $this->object);
    }

    /**
     * Tests how execute handles a single store setup with deltas disabled
     */
    public function testSingleStoreNotEnabled(): void
    {
        $this->setupStoreData([1 => false]);
        $this->object->execute();
    }

    /**
     * Tests how execute handles a single store setup with deltas enabled
     */
    public function testSingleStoreEnabled(): void
    {
        $this->setupStoreData([1 => true]);
        $this->object->execute();
    }

    /**
     * Tests how execute handles a multi store setup with deltas disabled on all
     */
    public function testMultiStoreNoneEnabled(): void
    {
        $this->setupStoreData([
            1 => false,
            17 => false,
            42 => false
        ]);

        $this->object->execute();
    }

    /**
     * Tests how execute handles a multi store setup with deltas enabled on one store
     */
    public function testMultiStoreOneEnabled(): void
    {
        $this->setupStoreData([
            1 => false,
            17 => true,
            42 => false
        ]);

        $this->object->execute();
    }

    /**
     * Tests how execute handles a multi store setup with deltas enabled on all stores
     */
    public function testMultiStoreAllEnabled(): void
    {
        $this->setupStoreData([
            1 => true,
            17 => true,
            42 => true
        ]);

        $this->object->execute();
    }
}
