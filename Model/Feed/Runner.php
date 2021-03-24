<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed;

use Exception;
use Pureclarity\Core\Api\FeedManagementInterface;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\FeedFactory;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed\State\Running;
use Pureclarity\Core\Model\Feed\State\RunDate;
use Pureclarity\Core\Model\Feed\State\Progress;
use Pureclarity\Core\Model\Feed\State\Error;
use PureClarity\Api\Feed\Feed;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class Runner
 *
 * Controls the execution of feeds sent to PureClarity.
 */
class Runner
{
    /** @var Data */
    private $coreHelper;

    /** @var FeedFactory */
    private $coreFeedFactory;

    /** @var StateRepositoryInterface */
    private $stateRepository;

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

    /**
     * @param Data $coreHelper
     * @param FeedFactory $coreFeedFactory
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     * @param Running $runningFeeds
     * @param RunDate $feedRunDate
     * @param Progress $feedProgress
     * @param Error $feedError
     * @param TypeHandler $feedTypeHandler
     */
    public function __construct(
        Data $coreHelper,
        FeedFactory $coreFeedFactory,
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger,
        CoreConfig $coreConfig,
        Running $runningFeeds,
        RunDate $feedRunDate,
        Progress $feedProgress,
        Error $feedError,
        TypeHandler $feedTypeHandler
    ) {
        $this->coreHelper      = $coreHelper;
        $this->coreFeedFactory = $coreFeedFactory;
        $this->stateRepository = $stateRepository;
        $this->logger          = $logger;
        $this->coreConfig      = $coreConfig;
        $this->runningFeeds    = $runningFeeds;
        $this->feedRunDate     = $feedRunDate;
        $this->feedProgress    = $feedProgress;
        $this->feedError       = $feedError;
        $this->feedTypeHandler = $feedTypeHandler;
    }

    /**
     * Runs all feed types for the given store
     * @param int $storeId
     */
    public function allFeeds(int $storeId): void
    {
        $this->doFeed([
            Feed::FEED_TYPE_PRODUCT,
            Feed::FEED_TYPE_CATEGORY,
            Feed::FEED_TYPE_BRAND,
            Feed::FEED_TYPE_USER
        ], $storeId);
    }

    /**
     * Runs the selected feeds array for the given store.
     *
     * @param int $storeId
     * @param array $feeds
     */
    public function selectedFeeds(int $storeId, array $feeds): void
    {
        $this->doFeed($feeds, $storeId);
    }

    /**
     * Produce a feed and POST to PureClarity.
     *
     * @param $feedTypes array
     * @param $storeId integer
     */
    public function doFeed(array $feedTypes, int $storeId): void
    {
        $progressFileName = $this->coreHelper->getProgressFileName();
        $feedModel = $this->coreFeedFactory
            ->create()
            ->initialise($storeId, $progressFileName);
        if (! $feedModel) {
            return;
        }

        $this->runningFeeds->setRunningFeeds($storeId, $feedTypes);
        // Post the feed data for the specified feed type
        foreach ($feedTypes as $key => $feedType) {
            switch ($feedType) {
                case Feed::FEED_TYPE_PRODUCT:
                    $feedModel->sendProducts();
                    $this->runningFeeds->removeRunningFeed($storeId, Feed::FEED_TYPE_PRODUCT);
                    $this->feedRunDate->setLastRunDate($storeId, Feed::FEED_TYPE_PRODUCT, date('Y-m-d H:i:s'));
                    break;
                case Feed::FEED_TYPE_CATEGORY:
                    $feedModel->sendCategories();
                    $this->runningFeeds->removeRunningFeed($storeId, Feed::FEED_TYPE_CATEGORY);
                    $this->feedRunDate->setLastRunDate($storeId, Feed::FEED_TYPE_CATEGORY, date('Y-m-d H:i:s'));
                    break;
                case Feed::FEED_TYPE_BRAND:
                    if ($this->coreConfig->isBrandFeedEnabled($storeId)) {
                        $feedModel->sendBrands();
                        $this->runningFeeds->removeRunningFeed($storeId, Feed::FEED_TYPE_BRAND);
                        $this->feedRunDate->setLastRunDate($storeId, Feed::FEED_TYPE_BRAND, date('Y-m-d H:i:s'));
                    }
                    break;
                case Feed::FEED_TYPE_USER:
                    $this->sendFeed($storeId, Feed::FEED_TYPE_USER);
                    $this->runningFeeds->removeRunningFeed($storeId, Feed::FEED_TYPE_USER);
                    $this->feedRunDate->setLastRunDate($storeId, Feed::FEED_TYPE_USER, date('Y-m-d H:i:s'));
                    break;
                case Feed::FEED_TYPE_ORDER:
                    $feedModel->sendOrders();
                    $this->runningFeeds->removeRunningFeed($storeId, Feed::FEED_TYPE_ORDER);
                    $this->feedRunDate->setLastRunDate($storeId, Feed::FEED_TYPE_ORDER, date('Y-m-d H:i:s'));
                    break;
                default:
                    throw new \InvalidArgumentException("PureClarity feed type not recognised: {$feedType}");
            }
        }
        $feedModel->checkSuccess();
        $this->setBannerStatus($storeId);
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
        try {
            $feedHandler = $this->feedTypeHandler->getFeedHandler($type);
            if ($feedHandler->isEnabled($storeId)) {
                $this->handleFeed($feedHandler, $storeId, $type);
            }
        } catch (Exception $e) {
            $this->logger->error('PureClarity: Error with ' . $type . ' feed: ' . $e->getMessage());
            $this->feedError->saveFeedError($storeId, $type, $e->getMessage());
        }
    }

    /**
     * Uses the provided feed handler to run a feed.
     *
     * @param FeedManagementInterface $feedHandler
     * @param int $storeId
     * @param string $type
     * @throws Exception
     */
    private function handleFeed(FeedManagementInterface $feedHandler, int $storeId, string $type) : void
    {
        $feedDataHandler = $feedHandler->getFeedDataHandler();
        $pageCount = $feedDataHandler->getTotalPages($storeId);

        if ($pageCount > 0) {

            $this->feedProgress->updateProgress($storeId, $type, '0');
            $feedBuilder = $feedHandler->getFeedBuilder(
                $this->coreConfig->getAccessKey($storeId),
                $this->coreConfig->getSecretKey($storeId),
                $this->coreConfig->getRegion($storeId)
            );

            $feedBuilder->start();

            $rowDataHandler = $feedHandler->getRowDataHandler();
            for ($page = 1; $page <= $pageCount; $page++) {
                $data = $feedDataHandler->getPageData($storeId, $page);
                foreach ($data as $row) {
                    $rowData = $rowDataHandler->getRowData($storeId, $row);
                    if ($rowData) {
                        $feedBuilder->append($rowData);
                    }
                }
                $this->feedProgress->updateProgress($storeId, $type, (string)round(($page / $pageCount) * 100));
            }

            $feedBuilder->end();
        }
    }

    /**
     * Sorts out the state for the banner display on the dashboard.
     * @param integer $storeId
     */
    private function setBannerStatus(int $storeId): void
    {
        try {

            $showBanner = $this->stateRepository->getByNameAndStore('show_welcome_banner', $storeId);

            if ($showBanner->getId()) {
                // set one day timer on getting started banner
                $gettingStarted = $this->stateRepository->getByNameAndStore(
                    'show_getting_started_banner',
                    $storeId
                );
                $gettingStarted->setName('show_getting_started_banner');
                $gettingStarted->setValue(time() + 86400);
                $gettingStarted->setStoreId($storeId);
                $this->stateRepository->save($gettingStarted);
                // Delete banner flags, no longer needed

                $this->stateRepository->delete($showBanner);
            }
        } catch (CouldNotSaveException $e) {
            $this->logger->error('Could not save banner status: ' . $e->getMessage());
        } catch (CouldNotDeleteException $e) {
            $this->logger->error('Could not delete banner flags: ' . $e->getMessage());
        }
    }
}
