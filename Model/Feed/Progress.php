<?php
declare(strict_types=1);

/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;

/**
 * Class Progress
 *
 * Handles feed progress data management
 */
class Progress
{
    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Filesystem $fileSystem */
    private $fileSystem;

    /** @var Data $coreHelper */
    private $coreHelper;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     * @param Filesystem $fileSystem
     * @param Data $coreHelper
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger,
        Filesystem $fileSystem,
        Data $coreHelper
    ) {
        $this->stateRepository = $stateRepository;
        $this->logger          = $logger;
        $this->fileSystem      = $fileSystem;
        $this->coreHelper      = $coreHelper;
    }

    /**
     * Resets the progress of feeds on a given store
     *
     * @param int $storeId
     */
    public function resetProgress(int $storeId)
    {
        $this->removeFeedError($storeId);
        $this->removeRunningFeeds($storeId);
        $this->deleteProgressFile();
    }

    /**
     * Resets the feed error status for the given store
     * @param integer $storeId
     * @return void
     */
    private function removeFeedError(int $storeId)
    {
        try {
            $state = $this->stateRepository->getByNameAndStore('last_feed_error', $storeId);
            if ($state->getId()) {
                $this->stateRepository->delete($state);
            }
        } catch (CouldNotDeleteException $e) {
            $this->logger->error('PureClarity: Could not clear last feed error: ' . $e->getMessage());
        }
    }

    /**
     * Removes the running_feeds state data (so dashboard shows correct feed status)
     * @param integer $storeId
     * @return void
     */
    private function removeRunningFeeds(int $storeId)
    {
        try {
            $state = $this->stateRepository->getByNameAndStore('running_feeds', $storeId);
            if ($state->getId()) {
                $this->stateRepository->delete($state);
            }
        } catch (CouldNotDeleteException $e) {
            $this->logger->error('PureClarity: Could not clear running feeds: ' . $e->getMessage());
        }
    }

    /**
     * Resets the feed error status for the given store
     * @return void
     */
    private function deleteProgressFile()
    {
        try {
            $fileReader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $fileWriter = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);

            if ($fileReader->isExist($this->coreHelper->getProgressFileName())) {
                $fileWriter->delete($this->coreHelper->getProgressFileName());
            }
        } catch (FileSystemException $e) {
            $this->logger->error('PureClarity: Error deleting progress file: ' . $e->getMessage());
        }
    }
}
