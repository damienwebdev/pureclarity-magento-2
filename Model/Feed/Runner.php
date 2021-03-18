<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Pureclarity\Core\Helper\Serializer;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed;
use Pureclarity\Core\Model\FeedFactory;

/**
 * Class Cron
 *
 * Controls the execution of feeds sent to PureClarity.
 */
class Runner
{
    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var FeedFactory $coreFeedFactory */
    private $coreFeedFactory;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /**
     * @param Data $coreHelper
     * @param FeedFactory $coreFeedFactory
     * @param StateRepositoryInterface $stateRepository
     * @param Serializer $serializer
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        Data $coreHelper,
        FeedFactory $coreFeedFactory,
        StateRepositoryInterface $stateRepository,
        Serializer $serializer,
        LoggerInterface $logger,
        CoreConfig $coreConfig
    ) {
        $this->coreHelper                   = $coreHelper;
        $this->coreFeedFactory              = $coreFeedFactory;
        $this->stateRepository              = $stateRepository;
        $this->serializer                   = $serializer;
        $this->logger                       = $logger;
        $this->coreConfig                   = $coreConfig;
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
     * Runs the selected feeds array for th egiven store.
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

        $this->logFeedQueue($feedTypes, $storeId);
        $feedsRemaining = $feedTypes;
        // Post the feed data for the specified feed type
        foreach ($feedTypes as $key => $feedType) {
            switch ($feedType) {
                case Feed::FEED_TYPE_PRODUCT:
                    $feedModel->sendProducts();
                    if (($key = array_search(Feed::FEED_TYPE_PRODUCT, $feedsRemaining)) !== false) {
                        unset($feedsRemaining[$key]);
                    }
                    $this->logFeedQueue($feedsRemaining, $storeId);
                    break;
                case Feed::FEED_TYPE_CATEGORY:
                    $feedModel->sendCategories();
                    if (($key = array_search(Feed::FEED_TYPE_CATEGORY, $feedsRemaining)) !== false) {
                        unset($feedsRemaining[$key]);
                    }
                    $this->logFeedQueue($feedsRemaining, $storeId);
                    break;
                case Feed::FEED_TYPE_BRAND:
                    if ($this->coreConfig->isBrandFeedEnabled($storeId)) {
                        $feedModel->sendBrands();
                        if (($key = array_search(Feed::FEED_TYPE_BRAND, $feedsRemaining)) !== false) {
                            unset($feedsRemaining[$key]);
                        }
                        $this->logFeedQueue($feedsRemaining, $storeId);
                    }
                    break;
                case Feed::FEED_TYPE_USER:
                    $feedModel->sendUsers();
                    if (($key = array_search(Feed::FEED_TYPE_USER, $feedsRemaining)) !== false) {
                        unset($feedsRemaining[$key]);
                    }
                    $this->logFeedQueue($feedsRemaining, $storeId);
                    break;
                case Feed::FEED_TYPE_ORDER:
                    $feedModel->sendOrders();
                    if (($key = array_search(Feed::FEED_TYPE_ORDER, $feedsRemaining)) !== false) {
                        unset($feedsRemaining[$key]);
                    }
                    $this->logFeedQueue($feedsRemaining, $storeId);
                    break;
                default:
                    throw new \InvalidArgumentException("PureClarity feed type not recognised: {$feedType}");
            }
        }
        $feedModel->checkSuccess();
        $this->setBannerStatus($storeId);
        $this->removeFeedQueue($storeId);
    }

    /**
     * Saves the running_feeds state data for remaining feeds to be run (so dashboard shows correct feed status)
     * @param string[] $feeds
     * @param integer $storeId
     * @return void
     */
    private function logFeedQueue(array $feeds, int $storeId): void
    {
        $state = $this->stateRepository->getByNameAndStore('running_feeds', $storeId);
        $state->setName('running_feeds');
        $state->setValue($this->serializer->serialize($feeds));
        $state->setStoreId($storeId);

        try {
            $this->stateRepository->save($state);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('Could not save queued feeds: ' . $e->getMessage());
        }
    }

    /**
     * Removes the running_feeds state data (so dashboard shows correct feed status)
     * @param integer $storeId
     * @return void
     */
    private function removeFeedQueue(int $storeId): void
    {
        $state = $this->stateRepository->getByNameAndStore('running_feeds', $storeId);

        try {
            $this->stateRepository->delete($state);
        } catch (CouldNotDeleteException $e) {
            $this->logger->error('Could not save queued feeds: ' . $e->getMessage());
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
