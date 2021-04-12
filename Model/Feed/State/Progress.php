<?php
declare(strict_types=1);

/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\State;

use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class Progress
 *
 * Handles feed progress data management
 */
class Progress
{
    /** @var StateRepositoryInterface */
    private $stateRepository;

    /** @var LoggerInterface */
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
     * Retrieves the progress value for the provided feed / store
     * @param int $storeId
     * @param string $feedType
     * @return string
     */
    public function getProgress(int $storeId, string $feedType): string
    {
        $state = $this->stateRepository->getByNameAndStore('feed_' . $feedType . '_progress', $storeId);
        return $state->getValue() ?: '';
    }

    /**
     * Saves the progress value for the provided feed / store
     * @param int $storeId
     * @param string $feedType
     * @param string $progressValue
     * @return void
     */
    public function updateProgress(int $storeId, string $feedType, string $progressValue): void
    {
        $state = $this->stateRepository->getByNameAndStore('feed_' . $feedType . '_progress', $storeId);
        $state->setName('feed_' . $feedType . '_progress');
        $state->setValue($progressValue);
        $state->setStoreId($storeId);

        try {
            $this->stateRepository->save($state);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('PureClarity: Could not save ' . $feedType . ' feed progress: ' . $e->getMessage());
        }
    }
}
