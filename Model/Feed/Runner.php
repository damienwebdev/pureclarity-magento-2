<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Dashboard\Banner;
use Pureclarity\Core\Model\Feed\State\Running;
use Pureclarity\Core\Model\Feed\State\RunDate;
use Pureclarity\Core\Model\Feed\State\Progress;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Store\Model\App\Emulation;
use PureClarity\Api\Feed\Feed;

/**
 * Class Runner
 *
 * Controls the execution of feeds sent to PureClarity.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Runner
{
    /** @var string[] */
    public const VALID_FEED_TYPES = [
        Feed::FEED_TYPE_PRODUCT,
        Feed::FEED_TYPE_CATEGORY,
        Feed::FEED_TYPE_BRAND,
        Feed::FEED_TYPE_USER,
        Feed::FEED_TYPE_ORDER
    ];

    /** @var StoreInterface */
    private $store;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Running */
    private $runningFeeds;

    /** @var RunDate */
    private $feedRunDate;

    /** @var Progress */
    private $feedProgress;

    /** @var Error */
    private $feedError;

    /** @var TypeHandler */
    private $feedTypeHandler;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Emulation */
    private $appEmulation;

    /** @var Banner */
    private $banner;

    /**
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     * @param Running $runningFeeds
     * @param RunDate $feedRunDate
     * @param Progress $feedProgress
     * @param Error $feedError
     * @param TypeHandler $feedTypeHandler
     * @param StoreManagerInterface $storeManager
     * @param Emulation $appEmulation
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        LoggerInterface $logger,
        CoreConfig $coreConfig,
        Running $runningFeeds,
        RunDate $feedRunDate,
        Progress $feedProgress,
        Error $feedError,
        TypeHandler $feedTypeHandler,
        StoreManagerInterface $storeManager,
        Emulation $appEmulation,
        Banner $banner
    ) {
        $this->logger          = $logger;
        $this->coreConfig      = $coreConfig;
        $this->runningFeeds    = $runningFeeds;
        $this->feedRunDate     = $feedRunDate;
        $this->feedProgress    = $feedProgress;
        $this->feedError       = $feedError;
        $this->feedTypeHandler = $feedTypeHandler;
        $this->storeManager    = $storeManager;
        $this->appEmulation    = $appEmulation;
        $this->banner          = $banner;
    }

    /**
     * Runs all feed types for the given store
     * @param int $storeId
     */
    public function allFeeds(int $storeId): void
    {
        if ($this->coreConfig->isActive($storeId)) {
            $this->doFeeds([
                Feed::FEED_TYPE_PRODUCT,
                Feed::FEED_TYPE_CATEGORY,
                Feed::FEED_TYPE_BRAND,
                Feed::FEED_TYPE_USER
            ], $storeId);
        }
    }

    /**
     * Runs the selected feeds array for the given store.
     *
     * @param int $storeId
     * @param array $feeds
     */
    public function selectedFeeds(int $storeId, array $feeds): void
    {
        if ($this->coreConfig->isActive($storeId)) {
            $this->doFeeds($feeds, $storeId);
        }
    }

    /**
     * Produce a feed and POST to PureClarity.
     *
     * @param $feedTypes array
     * @param $storeId integer
     */
    public function doFeeds(array $feedTypes, int $storeId): void
    {
        $this->runningFeeds->setRunningFeeds($storeId, $feedTypes);
        foreach ($feedTypes as $feedType) {
            if (in_array($feedType, self::VALID_FEED_TYPES, true)) {
                $this->sendFeed($storeId, $feedType);
                $this->feedRunDate->setLastRunDate($storeId, $feedType, date('Y-m-d H:i:s'));
            } else {
                $this->logger->error('PureClarity: Invalid feed type requested: ' . $feedType);
            }
            $this->runningFeeds->removeRunningFeed($storeId, $feedType);
        }
        $this->banner->removeWelcomeBanner($storeId);
        $this->runningFeeds->deleteRunningFeeds($storeId);
    }

    /**
     * Builds & sends a feed
     * @param int $storeId
     * @param string $type
     * @return void
     */
    public function sendFeed(int $storeId, string $type) : void
    {
        $feedHandler = $this->feedTypeHandler->getFeedHandler($type);
        try {
            if ($feedHandler->isEnabled($storeId)) {
                $store = $this->getStore($storeId);
                if ($feedHandler->requiresEmulation()) {
                    $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
                }
                $this->handleFeed($feedHandler, $store, $type);
                if ($feedHandler->requiresEmulation()) {
                    $this->appEmulation->stopEnvironmentEmulation();
                }
            }
        } catch (Exception $e) {
            if ($feedHandler->requiresEmulation()) {
                $this->appEmulation->stopEnvironmentEmulation();
            }
            $this->logger->error('PureClarity: Error with ' . $type . ' feed: ' . $e->getMessage());
            $this->feedError->saveFeedError($storeId, $type, $e->getMessage());
        }
    }

    /**
     * Uses the provided feed handler to run a feed.
     *
     * @param FeedManagementInterface $feedHandler
     * @param StoreInterface $store
     * @param string $type
     * @throws Exception
     */
    private function handleFeed(FeedManagementInterface $feedHandler, StoreInterface $store, string $type) : void
    {
        $feedDataHandler = $feedHandler->getFeedDataHandler();
        $pageCount = $feedDataHandler->getTotalPages($store);

        if ($pageCount > 0) {

            $this->feedProgress->updateProgress((int)$store->getId(), $type, '0');
            $feedBuilder = $feedHandler->getFeedBuilder(
                $this->coreConfig->getAccessKey((int)$store->getId()),
                $this->coreConfig->getSecretKey((int)$store->getId()),
                $this->coreConfig->getRegion((int)$store->getId())
            );

            $feedBuilder->start();

            $rowDataHandler = $feedHandler->getRowDataHandler();
            for ($page = 1; $page <= $pageCount; $page++) {
                $data = $feedDataHandler->getPageData($store, $page);
                foreach ($data as $row) {
                    $rowData = $rowDataHandler->getRowData($store, $row);
                    if ($rowData) {
                        $feedBuilder->append($rowData);
                    }
                }
                $this->feedProgress->updateProgress(
                    (int)$store->getId(),
                    $type,
                    (string)round(($page / $pageCount) * 100)
                );
            }

            $feedBuilder->end();
        }
    }

    /**
     * Gets a Store object for the given Store
     * @param int $storeId
     * @return StoreInterface|Store
     * @throws NoSuchEntityException
     */
    private function getStore(int $storeId): StoreInterface
    {
        if ($this->store === null) {
            $this->store = $this->storeManager->getStore($storeId);
        }
        return $this->store;
    }
}
