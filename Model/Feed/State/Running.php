<?php
declare(strict_types=1);

/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\State;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;

/**
 * Class Running
 *
 * Handles running_feeds feed state data management
 */
class Running
{
    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var SerializerInterface $serializer */
    private $serializer;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        $this->stateRepository = $stateRepository;
        $this->logger          = $logger;
        $this->serializer      = $serializer;
    }

    /**
     * Gets running_feeds state data for the given store ID
     * @param integer $storeId
     * @return array
     */
    public function getRunningFeeds(int $storeId): array
    {
        $state = $this->stateRepository->getByNameAndStore('running_feeds', $storeId);
        $value = $state->getValue();
        return $value ? $this->serializer->unserialize($value) : [];
    }

    /**
     * Saves running_feeds state data
     * @param array $feeds
     * @param integer $storeId
     * @return void
     */
    public function setRunningFeeds(int $storeId, array $feeds): void
    {
        $state = $this->stateRepository->getByNameAndStore('running_feeds', $storeId);
        $state->setName('running_feeds');
        $state->setValue($this->serializer->serialize($feeds));
        $state->setStoreId($storeId);

        try {
            $this->stateRepository->save($state);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('PureClarity: Could not save running feed data: ' . $e->getMessage());
        }
    }

    /**
     * Removes a feed from running_feeds state data
     * @param string $feed
     * @param integer $storeId
     * @return void
     */
    public function removeRunningFeed(int $storeId, string $feed): void
    {
        $state = $this->stateRepository->getByNameAndStore('running_feeds', $storeId);
        $value = $state->getValue();
        if ($value) {
            $feeds = $this->serializer->unserialize($value);
            if (($key = array_search($feed, $feeds, true)) !== false) {
                unset($feeds[$key]);
                sort($feeds);
            }
            $state->setValue($this->serializer->serialize($feeds));
            try {
                $this->stateRepository->save($state);
            } catch (CouldNotSaveException $e) {
                $this->logger->error('PureClarity: Could not remove running feed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Removes the running_feeds state data (so dashboard shows correct feed status)
     * @param integer $storeId
     * @return void
     */
    public function deleteRunningFeeds(int $storeId): void
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
}
