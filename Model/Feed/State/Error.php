<?php
declare(strict_types=1);

/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\State;

use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;

/**
 * Class Error
 *
 * Handles last_X_feed_error state table data management
 */
class Error
{
    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger
    ) {
        $this->stateRepository = $stateRepository;
        $this->logger          = $logger;
    }

    /**
     * Gets the feed error status for the given store and feed
     * @param integer $storeId
     * @param string $feed
     * @return string
     */
    public function getFeedError(int $storeId, string $feed): string
    {
        $state = $this->stateRepository->getByNameAndStore('last_' . $feed . '_feed_error', $storeId);
        return $state->getValue() ?: '';
    }

    /**
     * Saves the feed error status for the given store and feed
     * @param integer $storeId
     * @param string $feed
     * @param string $error
     * @return void
     */
    public function saveFeedError(int $storeId, string $feed, string $error): void
    {
        $state = $this->stateRepository->getByNameAndStore('last_' . $feed . '_feed_error', $storeId);
        $state->setName('last_' . $feed . '_feed_error');
        $state->setValue($error);
        $state->setStoreId($storeId);

        try {
            $this->stateRepository->save($state);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('PureClarity: Could not save feed error: ' . $e->getMessage());
        }
    }
}
