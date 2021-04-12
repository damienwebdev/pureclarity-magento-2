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
 * Class Request
 *
 * Handles requesting a feed (done via admin panel)
 */
class Request
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
     * Sets selected feeds to be run by cron asap
     *
     * @param integer $storeId
     * @param string[] $feeds
     */
    public function requestFeeds(int $storeId, array $feeds): void
    {
        try {
            $state = $this->stateRepository->getByNameAndStore('requested_feeds', $storeId);
            $state->setName('requested_feeds');
            $state->setValue($this->serializer->serialize($feeds));
            $state->setStoreId($storeId);
            $this->stateRepository->save($state);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('PureClarity: Could not request feeds: ' . $e->getMessage());
        }
    }

    /**
     * Gets requested feeds for all stores
     *
     * @return array[]
     */
    public function getAllRequestedFeeds() : array
    {
        $requestedFeeds = [];
        try {
            $requests = $this->stateRepository->getListByName('requested_feeds');
            foreach ($requests as $request) {
                $value = $request->getValue();
                if (!empty($value)) {
                    $requestedFeeds[$request->getStoreId()] = $this->serializer->unserialize($value);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('PureClarity: Could not load requested feeds: ' . $e->getMessage());
        }
        return $requestedFeeds;
    }

    /**
     * Gets requested feeds for a store
     *
     * @param int $storeId
     * @return string[]
     */
    public function getStoreRequestedFeeds(int $storeId) : array
    {
        $feeds = [];
        try {
            $state = $this->stateRepository->getByNameAndStore('requested_feeds', $storeId);
            $value = $state->getValue();
            if (!empty($value)) {
                $feeds = $this->serializer->unserialize($value);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'PureClarity: Could not load requested feeds for store ' . $storeId . ': ' . $e->getMessage()
            );
        }
        return $feeds;
    }

    /**
     * Removes requested feeds for the given store
     *
     * @param integer $storeId
     */
    public function deleteRequestedFeeds(int $storeId): void
    {
        try {
            $state = $this->stateRepository->getByNameAndStore('requested_feeds', $storeId);
            if ($state->getId()) {
                $this->stateRepository->delete($state);
            }
        } catch (CouldNotDeleteException $e) {
            $this->logger->error(
                'PureClarity: Could not delete requested feeds for store ' . $storeId . ': ' . $e->getMessage()
            );
        }
    }
}
