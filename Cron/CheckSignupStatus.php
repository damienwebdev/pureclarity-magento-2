<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Cron;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\Signup\Process;
use Pureclarity\Core\Model\Signup\Status as RequestStatus;

/**
 * Class CheckSignupStatus
 *
 * Checks the PureClarity signup status
 */
class CheckSignupStatus
{
    /** @var RequestStatus $url*/
    private $requestStatus;

    /** @var Process $curl */
    private $requestProcess;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var State $state */
    private $state;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /**
     * @param RequestStatus $requestStatus
     * @param Process $requestProcess
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     * @param State $state
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RequestStatus $requestStatus,
        Process $requestProcess,
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger,
        State $state,
        StoreManagerInterface $storeManager
    ) {
        $this->requestStatus   = $requestStatus;
        $this->requestProcess  = $requestProcess;
        $this->stateRepository = $stateRepository;
        $this->logger          = $logger;
        $this->state           = $state;
        $this->storeManager    = $storeManager;
    }

    /**
     * Checks to see if there is a signup request in progress, and if so checks it's status and processes if necessary
     *
     * This is a catcher for if the user navigates away from setup page when
     */
    public function execute()
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $e) {
            $this->logger->error('PureClarity Setup Warning: ' . $e->getMessage());
        }

        if ($this->storeManager->hasSingleStore() === false) {
            // is multi-store, check all stores for signup
            foreach ($this->storeManager->getStores() as $store) {
                $this->checkSignup((int)$store->getId());
            }
        } else {
            // single store so set this to 0
            $this->checkSignup(0);
        }
    }
    /**
     * Checks to see if there is a signup request in progress for the given store
     * and if so checks it's status and processes if necessary
     * @param $storeId
     */
    public function checkSignup($storeId)
    {
        $signupState = $this->stateRepository->getByNameAndStore('signup_request', $storeId);

        if ($signupState->getId() !== null) {
            $response = $this->requestStatus->checkStatus($storeId);
            if ($response['complete'] === true) {
                $this->requestProcess->process($response['response']);
            } elseif ($response['error']) {
                $this->logger->error('PureClarity Setup Error: ' . $response['error']);
            }
        }
    }
}
