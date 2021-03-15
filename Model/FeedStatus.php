<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Helper\Serializer;
use Pureclarity\Core\Model\Feed\Request;

/**
 * Class FeedStatus
 *
 * Feed status checker model
 */
class FeedStatus
{
    /** @var mixed[] $feedStatusData */
    private $feedStatusData;

    /** @var array[] $feedErrors */
    private $feedErrors;

    /** @var mixed[] $progressData */
    private $progressData;

    /** @var array[] $requestedFeedData */
    private $requestedFeedData = [];

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var Filesystem $fileSystem */
    private $fileSystem;

    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var TimezoneInterface $timezone */
    private $timezone;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Request $feedRequest */
    private $feedRequest;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param Filesystem $fileSystem
     * @param Data $coreHelper
     * @param CoreConfig $coreConfig
     * @param Serializer $serializer
     * @param TimezoneInterface $timezone
     * @param LoggerInterface $logger
     * @param Request $feedRequest
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        Filesystem $fileSystem,
        Data $coreHelper,
        CoreConfig $coreConfig,
        Serializer $serializer,
        TimezoneInterface $timezone,
        LoggerInterface $logger,
        Request $feedRequest
    ) {
        $this->stateRepository = $stateRepository;
        $this->fileSystem      = $fileSystem;
        $this->coreHelper      = $coreHelper;
        $this->coreConfig      = $coreConfig;
        $this->serializer      = $serializer;
        $this->timezone        = $timezone;
        $this->logger          = $logger;
        $this->feedRequest     = $feedRequest;
    }

    /**
     * Returns whether any of the feed types provided are currently in progress
     *
     * @param string[] $types
     * @param integer $storeId
     *
     * @return bool
     */
    public function getAreFeedsInProgress($types, $storeId = 0)
    {
        $inProgress = false;
        foreach ($types as $type) {
            $status = $this->getFeedStatus($type, $storeId);
            if ($status['running'] === true) {
                $inProgress = true;
            }
        }

        return $inProgress;
    }

    /**
     * Returns whether all of the feed types provided are currently disabled
     *
     * @param string[] $types
     * @param integer $storeId
     *
     * @return bool
     */
    public function getAreFeedsDisabled($types, $storeId = 0)
    {
        $disabled = true;
        foreach ($types as $type) {
            $status = $this->getFeedStatus($type, $storeId);
            if ($status['enabled'] === true) {
                $disabled = false;
                break;
            }
        }

        return $disabled;
    }

    /**
     * Returns the status of the product feed
     *
     * @param string $type
     * @param integer $storeId
     * @return mixed[]
     */
    public function getFeedStatus($type, $storeId = 0)
    {
        if (!isset($this->feedStatusData[$type])) {
            $status = [
                'enabled' => true,
                'error' => false,
                'running' => false,
                'class' => 'pc-feed-not-sent',
                'label' => __('Not Sent')
            ];

            if ($this->coreConfig->isActive($storeId) === false) {
                $status['enabled'] = false;
                $status['label'] = __('Not Enabled');
                $status['class'] = 'pc-feed-disabled';
            }

            if ($type === 'brand' &&
                $status['enabled'] === true &&
                $this->coreConfig->isBrandFeedEnabled($storeId) === false
            ) {
                $status['enabled'] = false;
                $status['label'] = __('Not Enabled');
                $status['class'] = 'pc-feed-disabled';
            }

            if ($status['enabled'] === false) {
                $this->feedStatusData[$type] = $status;
                return $this->feedStatusData[$type];
            }

            if ($this->hasFeedError($type, $storeId) === true) {
                $status['error'] = true;
                $status['label'] = __('Error, please see logs for more information');
                $status['class'] = 'pc-feed-error';
                $this->feedStatusData[$type] = $status;
                return $this->feedStatusData[$type];
            }

            // check if it's been requested
            $requested = $this->hasFeedBeenRequested($type, $storeId);

            if ($requested) {
                $status['running'] = true;
                $status['label'] = __('Waiting for feed run to start');
                $status['class'] = 'pc-feed-waiting';
            }

            // check if it's been requested
            $requested = $this->isFeedWaiting($type, $storeId);

            if ($requested) {
                $status['running'] = true;
                $status['label'] = __('Waiting for other feeds to finish');
                $status['class'] = 'pc-feed-waiting';
            }

            if ($status['running']) {
                // check if it's in progress
                $progress = $this->feedProgress($type);
                if ($progress !== false) {
                    $status['running'] = true;
                    $status['label'] = __('In progress: %1%', $progress);
                    $status['class'] = 'pc-feed-in-progress';
                }
            }

            if ($status['running'] !== true) {
                // check it's last run date
                $state = $this->stateRepository->getByNameAndStore('last_' . $type . '_feed_date', $storeId);
                $lastProductFeedDate = ($state->getId() !== null) ? $state->getValue() : '';
                if ($lastProductFeedDate) {
                    $status['label'] = __(
                        'Last sent %1',
                        $this->timezone->formatDate(
                            $lastProductFeedDate,
                            \IntlDateFormatter::SHORT,
                            true
                        )
                    );
                    $status['class'] = 'pc-feed-complete';
                }
            }

            $this->feedStatusData[$type] = $status;
        }

        return $this->feedStatusData[$type];
    }

    /**
     * Checks for the requested feeds for the store and returns whether the given feed type is in it's data
     *
     * @param string $feedType
     * @param int $storeId
     * @return bool
     */
    private function hasFeedBeenRequested(string $feedType, int $storeId) : bool
    {
        $feedRequest = $this->getRequestFeedData($storeId);
        return in_array($feedType, $feedRequest, true);
    }

    /**
     * Checks for the last_feed_error state row and returns whether the given feed type is in it's data
     *
     * @param string $feedType
     * @param integer $storeId
     * @return bool
     */
    private function hasFeedError($feedType, $storeId)
    {
        $error = false;

        if (!isset($this->feedErrors[$storeId]) || $this->feedErrors[$storeId] === null) {
            $state = $this->stateRepository->getByNameAndStore('last_feed_error', $storeId);
            $this->feedErrors[$storeId] = ($state->getId() !== null) ? explode(',', $state->getValue()) : [];
        }

        if (!empty($this->feedErrors[$storeId])) {
            $error = in_array($feedType, $this->feedErrors[$storeId]);
        }

        return $error;
    }

    /**
     * Checks for the running_feeds state row and returns whether the given feed type is in it's data
     *
     * @param string $feedType
     * @param integer $storeId
     * @return bool
     */
    private function isFeedWaiting($feedType, $storeId)
    {
        $waiting = false;
        $state = $this->stateRepository->getByNameAndStore('running_feeds', $storeId);
        $waitingFeedsRaw = ($state->getId() !== null) ? $state->getValue() : '';

        if ($waitingFeedsRaw) {
            $waitingFeeds = $this->serializer->unserialize($waitingFeedsRaw);
            $waiting = in_array($feedType, $waitingFeeds);
        }

        return $waiting;
    }

    /**
     * Calculates progress based on the data in the progress file
     *
     * @param string $feedType
     * @return bool|float
     */
    private function feedProgress($feedType)
    {
        $inProgress = false;
        $progressData = $this->getProgressData();

        if (!empty($progressData) && $progressData['name'] === $feedType) {
            $inProgress = round(($progressData['cur'] / $progressData['max']) * 100);
        }

        return $inProgress;
    }

    /**
     * Gets progress file data from the filesystem
     *
     * @return bool
     */
    private function getProgressData()
    {
        if ($this->progressData === null) {
            $this->progressData = [];
            $progressFileName = $this->coreHelper->getProgressFileName();
            /** @var ReadInterface $fileReader */
            $fileReader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);

            if ($fileReader->isExist($progressFileName)) {
                try {
                    $progressData = $fileReader->readFile($progressFileName);
                    $this->progressData = $this->serializer->unserialize($progressData);
                } catch (FileSystemException $e) {
                    $this->logger->error('Could not get PureClarity feed progress data: ' . $e->getMessage());
                }
            }
        }

        return $this->progressData;
    }

    /**
     * Gets requested feeds data for the given store
     * @param int $storeId
     * @return string[]
     */
    private function getRequestFeedData(int $storeId) : array
    {
        if (!isset($this->requestedFeedData[$storeId])) {
            $this->requestedFeedData[$storeId] = $this->feedRequest->getStoreRequestedFeeds($storeId);
        }

        return $this->requestedFeedData[$storeId];
    }
}
