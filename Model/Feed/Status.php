<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PureClarity\Api\Feed\Feed;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed\State\Error;
use Pureclarity\Core\Model\Feed\State\Progress;
use Pureclarity\Core\Model\Feed\State\Request;
use Pureclarity\Core\Model\Feed\State\RunDate;
use Pureclarity\Core\Model\Feed\State\Running;

/**
 * Class Status
 *
 * Feed status checker model
 */
class Status
{
    /** @var mixed[] */
    private $feedStatusData;

    /** @var mixed[] */
    private $requestedFeeds;

    /** @var array[] */
    private $feedErrors;

    /** @var mixed[] */
    private $waitingFeeds;

    /** @var mixed[] */
    private $feedProgress;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var TimezoneInterface */
    private $timezone;

    /** @var Error */
    private $error;

    /** @var Progress */
    private $progress;

    /** @var Request */
    private $request;

    /** @var RunDate */
    private $runDate;

    /** @var Running */
    private $running;

    /**
     * @param CoreConfig $coreConfig
     * @param TimezoneInterface $timezone
     * @param Error $error
     * @param Progress $progress
     * @param Request $request
     * @param RunDate $runDate
     * @param Running $running
     */
    public function __construct(
        CoreConfig $coreConfig,
        TimezoneInterface $timezone,
        Error $error,
        Progress $progress,
        Request $request,
        RunDate $runDate,
        Running $running
    ) {
        $this->coreConfig = $coreConfig;
        $this->timezone   = $timezone;
        $this->error      = $error;
        $this->progress   = $progress;
        $this->request    = $request;
        $this->runDate    = $runDate;
        $this->running    = $running;
    }

    /**
     * Returns whether any of the feed types provided are currently in progress
     *
     * @param string[] $types
     * @param integer $storeId
     *
     * @return bool
     */
    //public function getAreFeedsInProgress($types, $storeId = 0)
    public function areFeedsInProgress(array $types, int $storeId = 0): bool
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
     * Returns whether any of the feed types provided are currently in progress
     *
     * @param string[] $types
     * @param integer $storeId
     *
     * @return bool
     */
    //public function getAreFeedsDisabled($types, $storeId = 0)
    public function areFeedsDisabled(array $types, int $storeId = 0): bool
    {
        $disabled = true;
        foreach ($types as $type) {
            if ($this->isDisabled($type, $storeId) === false) {
                $disabled = false;
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
    public function getFeedStatus(string $type, $storeId = 0): array
    {
        if (!isset($this->feedStatusData[$type])) {
            $this->feedStatusData[$type] = $this->processFeedStatus($type, $storeId);
        }

        return $this->feedStatusData[$type];
    }

    /**
     * Returns the status of the product feed
     *
     * @param string $type
     * @param integer $storeId
     * @return mixed[]
     */
    private function processFeedStatus(string $type, $storeId = 0): array
    {
        $status = [
            'enabled' => true,
            'error' => false,
            'running' => false,
            'class' => 'pc-feed-not-sent',
            'label' => __('Not Sent')
        ];

        if ($this->isDisabled($type, $storeId)) {
            $status['enabled'] = false;
            $status['label'] = __('Not Enabled');
            $status['class'] = 'pc-feed-disabled';
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

        if ($this->hasFeedBeenRequested($type, $storeId)) {
            $status['running'] = true;
            $status['label'] = __('Waiting for feed run to start');
            $status['class'] = 'pc-feed-waiting';
        }

        if ($this->isFeedWaiting($type, $storeId)) {
            $status['running'] = true;
            $status['label'] = __('Waiting for other feeds to finish');
            $status['class'] = 'pc-feed-waiting';
        }

        if ($status['running']) {
            // check if it's in progress
            $progress = $this->feedProgress($type, $storeId);
            if ($progress) {
                $status['running'] = true;
                $status['label'] = __('In progress: %1%', $progress);
                $status['class'] = 'pc-feed-in-progress';
            }
        } else {
            // check it's last run date
            $lastRunDate = $this->runDate->getLastRunDate($storeId, $type);
            if ($lastRunDate) {
                $status['label'] = __(
                    'Last sent %1',
                    $this->timezone->formatDateTime($lastRunDate)
                );
                $status['class'] = 'pc-feed-complete';
            }
        }

        return $status;
    }

    /**
     * Returns whether a feed is disabled or not.
     *
     * @param string $type
     * @param int $storeId
     * @return bool
     */
    private function isDisabled(string $type, int $storeId): bool
    {
        return $this->coreConfig->isActive($storeId) === false
            || ($type === Feed::FEED_TYPE_BRAND &&
                ($this->coreConfig->isActive($storeId) === false ||
                $this->coreConfig->isBrandFeedEnabled($storeId) === false));
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
        if (!isset($this->requestedFeeds[$storeId])) {
            $this->requestedFeeds[$storeId] = $this->request->getStoreRequestedFeeds($storeId);
        }

        return in_array($feedType, $this->requestedFeeds[$storeId], true);
    }

    /**
     * Checks for the last_feed_error state row and returns whether the given feed type is in it's data
     *
     * @param string $feedType
     * @param integer $storeId
     * @return bool
     */
    private function hasFeedError(string $feedType, int $storeId): bool
    {
        $key = $feedType . $storeId;
        if (!isset($this->feedErrors[$key])) {
            $this->feedErrors[$key] = $this->error->getFeedError($storeId, $feedType);
        }

        return !empty($this->feedErrors[$key]);
    }

    /**
     * Checks for the running_feeds state row and returns whether the given feed type is in it's data
     *
     * @param string $feedType
     * @param integer $storeId
     * @return bool
     */
    private function isFeedWaiting(string $feedType, int $storeId): bool
    {
        if (!isset($this->waitingFeeds[$storeId])) {
            $this->waitingFeeds[$storeId] = $this->running->getRunningFeeds($storeId);
        }

        return in_array($feedType, $this->waitingFeeds[$storeId], true);
    }

    /**
     * Gets the feed progress data
     *
     * @param string $feedType
     * @param int $storeId
     * @return string
     */
    private function feedProgress(string $feedType, int $storeId): string
    {
        $key = $feedType . $storeId;
        if (!isset($this->feedProgress[$key])) {
            $this->feedProgress[$key] = $this->progress->getProgress($storeId, $feedType);
        }

        return $this->feedProgress[$key];
    }
}
