<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Dashboard;

use Pureclarity\Core\Api\StateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class Banner
 *
 * Controls the reset of the banner shown on the dashboard after signup.
 */
class Banner
{
    /** @var StateRepositoryInterface */
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
     * Sorts out the state for the banner display on the dashboard.
     * @param integer $storeId
     */
    public function removeWelcomeBanner(int $storeId): void
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
            $this->logger->error('PureClarity: Could not save banner status: ' . $e->getMessage());
        } catch (CouldNotDeleteException $e) {
            $this->logger->error('PureClarity: Could not delete banner flags: ' . $e->getMessage());
        }
    }
}
