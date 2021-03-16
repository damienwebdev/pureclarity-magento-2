<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Delta;

use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Cron;
use Pureclarity\Core\Model\ProductFeed;
use Pureclarity\Core\Model\ResourceModel\ProductFeed\CollectionFactory;
use Pureclarity\Core\Model\Delta\Type\Product;
use Pureclarity\Core\Model\ResourceModel\ProductFeed as ProductFeedResourceModel;

/**
 * Class Runner
 *
 * Controls the execution of all Delta types.
 */
class Runner
{
    /** @var string */
    private const DELTA_TYPE_CATEGORY = "category";

    /** @var string */
    private const DELTA_TYPE_PRODUCT = "product";

    /**
     * @var string[]
     */
    private $deltaTypes = [
        self::DELTA_TYPE_CATEGORY,
        self::DELTA_TYPE_PRODUCT
    ];

    /** @var array */
    private $deltaData = [];

    /** @var CollectionFactory $deltaIndexCollectionFactory */
    private $deltaIndexCollectionFactory;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Cron $feedRunner */
    private $feedRunner;

    /** @var ProductFeedResourceModel */
    private $deltaResourceModel;

    /** @var Product $productDeltaRunner */
    private $productDeltaRunner;

    /**
     * @param CollectionFactory $deltaIndexCollectionFactory
     * @param LoggerInterface $logger
     * @param Cron $feedRunner
     * @param Product $productDeltaRunner
     * @param ProductFeedResourceModel $deltaResourceModel
     */
    public function __construct(
        CollectionFactory $deltaIndexCollectionFactory,
        LoggerInterface $logger,
        Cron $feedRunner,
        ProductFeedResourceModel $deltaResourceModel,
        Product $productDeltaRunner
    ) {
        $this->deltaIndexCollectionFactory  = $deltaIndexCollectionFactory;
        $this->logger                       = $logger;
        $this->feedRunner                   = $feedRunner;
        $this->deltaResourceModel           = $deltaResourceModel;
        $this->productDeltaRunner           = $productDeltaRunner;
    }

    /**
     * Runs deltas for all delta types
     * @param int $storeId
     */
    public function runDeltas(int $storeId): void
    {
        foreach ($this->deltaTypes as $type) {
            $runFeed = $this->isFullFeedRequired($type);
            $this->cleanupDeltas($type);
            if ($runFeed) {
                $this->feedRunner->selectedFeeds($storeId, [$type]);
            } else {
                $deltaIds = $this->getDeltaIds($type);
                $deltaRunner = $this->getDeltaRunner($type);
                if (!empty($deltaIds) && $deltaRunner) {
                    $deltaRunner->runDelta($storeId, $deltaIds);
                }
            }
        }
    }

    /**
     * Checks if a full feed of the given type is required.
     *
     * Will be true if a row is present with the id of -1
     *
     * @param string $type
     * @return bool
     */
    public function isFullFeedRequired(string $type): bool
    {
        $deltaData = $this->getDeltaData($type);

        $fullFeed = false;
        foreach ($deltaData as $deltaRow) {
            if ($deltaRow->getProductId() === '-1') {
                $fullFeed = true;
            }
        }

        return $fullFeed;
    }

    /**
     * Gets all entity IDs for delta rows for the given type
     *
     * @param string $type
     * @return array
     */
    public function getDeltaIds(string $type): array
    {
        $deltaData = $this->getDeltaData($type);

        $ids = [];
        foreach ($deltaData as $deltaRow) {
            $ids[] = $deltaRow->getProductId();
        }
        return array_unique($ids);
    }

    /**
     * Gets the delta runner for the given delta type
     *
     * @param string $type
     * @return null|Product
     */
    public function getDeltaRunner(string $type): ?Product
    {
        $runner = null;
        switch ($type) {
            case self::DELTA_TYPE_PRODUCT:
                $runner = $this->productDeltaRunner;
                break;
        }

        return $runner;
    }

    /**
     * Loads delta data for the given delta type
     *
     * @param string $type
     * @return ProductFeed[]
     */
    public function getDeltaData(string $type): array
    {
        if (!isset($this->deltaData[$type])) {
            $this->deltaData[$type] = [];
            $collection = $this->deltaIndexCollectionFactory->create();
            $collection->addFieldToFilter('status_id', ['eq' => 0]);
            $collection->addFieldToFilter('token', $type);
            $this->deltaData[$type] = $collection->getItems();
        }

        return $this->deltaData[$type];
    }

    /**
     * Deletes delta data for the given type.
     *
     * @param string $type
     */
    public function cleanupDeltas(string $type): void
    {
        $deltaData = $this->getDeltaData($type);

        try {
            foreach ($deltaData as $deltaRow) {
                $this->deltaResourceModel->delete($deltaRow);
            }
        } catch (\Exception $e) {
            $this->logger->error('PureClarity: Error deleting delta rows: ' . $e->getMessage());
        }
    }
}
