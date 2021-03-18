<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Pureclarity\Core\Helper\Serializer;
use Magento\Store\Model\StoreFactory;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;

/**
 * Class Cron
 *
 * Controls the execution of feeds sent to PureClarity.
 */
class Cron
{
    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var FeedFactory $coreFeedFactory */
    private $coreFeedFactory;

    /** @var StoreFactory $storeStoreFactory */
    private $storeStoreFactory;

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
     * @param StoreFactory $storeStoreFactory
     * @param StateRepositoryInterface $stateRepository
     * @param Serializer $serializer
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        Data $coreHelper,
        FeedFactory $coreFeedFactory,
        StoreFactory $storeStoreFactory,
        StateRepositoryInterface $stateRepository,
        Serializer $serializer,
        LoggerInterface $logger,
        CoreConfig $coreConfig
    ) {
        $this->coreHelper                   = $coreHelper;
        $this->coreFeedFactory              = $coreFeedFactory;
        $this->storeStoreFactory            = $storeStoreFactory;
        $this->stateRepository              = $stateRepository;
        $this->serializer                   = $serializer;
        $this->logger                       = $logger;
        $this->coreConfig                   = $coreConfig;
    }

    // Produce all feeds in one file.
    public function allFeeds($storeId)
    {
        $this->doFeed([
            Feed::FEED_TYPE_PRODUCT,
            Feed::FEED_TYPE_CATEGORY,
            Feed::FEED_TYPE_BRAND,
            Feed::FEED_TYPE_USER
        ], $storeId, $this->getFeedFilePath('all', $storeId));
    }
    
    public function selectedFeeds($storeId, $feeds)
    {
        $this->doFeed($feeds, $storeId, $this->getFeedFilePath('all', $storeId));
    }

    /**
     * Produce a feed and POST to PureClarity.
     * @param $feedTypes array
     * @param $storeId integer
     * @param $feedFilePath
     */
    public function doFeed($feedTypes, $storeId, $feedFilePath)
    {
        $hasOrder = in_array(Feed::FEED_TYPE_ORDER, $feedTypes);
        $isOrderOnly = ($hasOrder && count($feedTypes) == 1);

        $progressFileName = $this->coreHelper->getProgressFileName();
        $feedModel = $this->coreFeedFactory
            ->create()
            ->initialise($storeId, $progressFileName);
        if (! $feedModel) {
            return false;
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

    private function getFeedFilePath($feedType, $storeId)
    {
        $store = $this->storeStoreFactory->create()->load($storeId);
        return $this->coreHelper->getPureClarityBaseDir()
                . DIRECTORY_SEPARATOR
                . $this->coreHelper->getFileNameForFeed($feedType, $store->getCode());
    }

    /**
     * Saves the running_feeds state data for remaining feeds to be run (so dashboard shows correct feed status)
     * @param string[] $feeds
     * @param integer $storeId
     * @return void
     */
    private function logFeedQueue($feeds, $storeId)
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
    private function removeFeedQueue($storeId)
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
    private function setBannerStatus($storeId)
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
