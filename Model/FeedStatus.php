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
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class FeedStatus
 *
 * Feed status checker model
 */
class FeedStatus implements ArgumentInterface
{
    /** @var mixed[] $progressData */
    private $progressData;

    /** @var mixed[] $requestedFeedData */
    private $requestedFeedData;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var Filesystem $fileSystem */
    private $fileSystem;

    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var Json $json */
    private $json;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param Filesystem $fileSystem
     * @param Data $coreHelper
     * @param Json $json
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        Filesystem $fileSystem,
        Data $coreHelper,
        Json $json
    ) {
        $this->stateRepository = $stateRepository;
        $this->fileSystem      = $fileSystem;
        $this->coreHelper      = $coreHelper;
        $this->json            = $json;
    }

    /**
     * Returns the status of the product feed
     *
     * @param string $type
     * @return Phrase|string
     */
    public function getFeedStatus($type)
    {
        $status = __('Not Sent');

        // check if it's been requested
        $requested = $this->hasFeedBeenRequested($type);

        if ($requested) {
            $status = __('Feed scheduled');
        }

        // check is it's in progress
        $progress = $this->feedProgress($type);
        if ($progress !== false) {
            $status = __('In progress: %1%', $progress);
        }

        // check it's last run date
        $state = $this->stateRepository->getByName('last_' . $type . '_feed_date');
        $lastProductFeedDate = ($state->getId() !== null) ? $state->getValue() : '';
        if ($lastProductFeedDate) {
            $status = __('Last complete run:') . $lastProductFeedDate;
        }

        return $status;
    }

    /**
     * @param $feedType
     * @return bool
     */
    private function hasFeedBeenRequested($feedType)
    {
        $requested = false;
        $scheduleData = $this->getScheduledFeedData();

        if (!empty($scheduleData)) {
            $requested = in_array($feedType, $scheduleData['feeds']);
        }

        return $requested;
    }

    /**
     * @param $feedType
     * @return bool|float
     */
    private function feedProgress($feedType)
    {
        $inProgress = false;
        $progressData = $this->getProgressData();

        if (!empty($progressData) && $progressData['name'] === $feedType) {
            $inProgress = round($progressData['cur'] / $progressData['max']);
        }

        return $inProgress;
    }

    /**
     * @return bool
     */
    private function getProgressData()
    {
        if ($this->progressData === null) {
            $progressFileName = $this->coreHelper->getProgressFileName();
            /** @var ReadInterface $fileReader */
            $fileReader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);

            if ($fileReader->isExist($progressFileName)) {
                try {
                    $progressData = $fileReader->readFile($progressFileName);
                    $this->progressData = $this->json->unserialize($progressData);
                } catch (FileSystemException $e) {
                    $this->requestedFeedData = [];
                }
            }
        }

        return $this->progressData;
    }

    /**
     * @return bool
     */
    private function getScheduledFeedData()
    {
        if ($this->requestedFeedData === null) {
            $scheduleFile = $this->coreHelper->getPureClarityBaseDir() . DIRECTORY_SEPARATOR . 'scheduled_feed';
            /** @var ReadInterface $fileReader */
            $fileReader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);

            if ($fileReader->isExist($scheduleFile)) {
                try {
                    $scheduledData = $fileReader->readFile($scheduleFile);
                    $this->requestedFeedData = $this->json->unserialize($scheduledData);
                } catch (FileSystemException $e) {
                    $this->requestedFeedData = [];
                }
            }
        }

        return $this->requestedFeedData;
    }
}
