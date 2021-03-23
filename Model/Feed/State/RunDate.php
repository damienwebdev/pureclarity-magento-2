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
 * Class RunDate
 *
 * Handles last_X_feed_date state table data management
 */
class RunDate
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
     * Gets the last run date of the provided feed on the given store ID
     * @param integer $storeId
     * @param string $feedType
     * @return string
     */
    public function getLastRunDate(int $storeId, string $feedType): string
    {
        $state = $this->stateRepository->getByNameAndStore('last_' . $feedType . '_feed_date', $storeId);
        return $state->getValue() ?: '';
    }

    /**
     * Saves the last run date of the provided feed & store
     * @param integer $storeId
     * @param string $feedType
     * @param string $date
     * @return void
     */
    public function setLastRunDate(int $storeId, string $feedType, string $date): void
    {
        $state = $this->stateRepository->getByNameAndStore('last_' . $feedType . '_feed_date', $storeId);
        $state->setName('last_' . $feedType . '_feed_date');
        $state->setValue($date);
        $state->setStoreId($storeId);

        try {
            $this->stateRepository->save($state);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('PureClarity: Could not save last updated date: ' . $e->getMessage());
        }
    }
}
