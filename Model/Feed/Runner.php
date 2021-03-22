<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed;
use Pureclarity\Core\Model\FeedFactory;
use Pureclarity\Core\Model\Feed\Type\User;

/**
 * Class Runner
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

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Running */
    private $runningFeeds;

    /** @var User */
    private $userFeed;

    /**
     * @param Data $coreHelper
     * @param FeedFactory $coreFeedFactory
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     * @param Running $runningFeeds
     * @param User $userFeed
     */
    public function __construct(
        Data $coreHelper,
        FeedFactory $coreFeedFactory,
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger,
        CoreConfig $coreConfig,
        Running $runningFeeds,
        User $userFeed
    ) {
        $this->coreHelper      = $coreHelper;
        $this->coreFeedFactory = $coreFeedFactory;
        $this->stateRepository = $stateRepository;
        $this->logger          = $logger;
        $this->coreConfig      = $coreConfig;
        $this->runningFeeds    = $runningFeeds;
        $this->userFeed        = $userFeed;
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
                    break;
                case Feed::FEED_TYPE_CATEGORY:
                    $feedModel->sendCategories();
                    $this->runningFeeds->removeRunningFeed($storeId, Feed::FEED_TYPE_CATEGORY);
                    break;
                case Feed::FEED_TYPE_BRAND:
                    if ($this->coreConfig->isBrandFeedEnabled($storeId)) {
                        $feedModel->sendBrands();
                        $this->runningFeeds->removeRunningFeed($storeId, Feed::FEED_TYPE_BRAND);
                    }
                    break;
                case Feed::FEED_TYPE_USER:
                    $this->userFeed->send($storeId);
                    $this->runningFeeds->removeRunningFeed($storeId, Feed::FEED_TYPE_USER);
                    break;
                case Feed::FEED_TYPE_ORDER:
                    $feedModel->sendOrders();
                    $this->runningFeeds->removeRunningFeed($storeId, Feed::FEED_TYPE_ORDER);
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
