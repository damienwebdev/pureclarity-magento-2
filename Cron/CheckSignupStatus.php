<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Cron;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
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

    /** @var State $state*/
    private $state;

    /**
     * @param RequestStatus $requestStatus
     * @param Process $requestProcess
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     * @param State $state
     */
    public function __construct(
        RequestStatus $requestStatus,
        Process $requestProcess,
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger,
        State $state
    ) {
        $this->requestStatus   = $requestStatus;
        $this->requestProcess  = $requestProcess;
        $this->stateRepository = $stateRepository;
        $this->logger          = $logger;
        $this->state           = $state;
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
            $this->logger->error('PureClarity Setup Warning: ' .$e->getMessage());
        }

        $isConfiguredState = $this->stateRepository->getByNameAndStore('is_configured', 0);

        if ($isConfiguredState->getId() === null ||
            ($isConfiguredState->getId() && $isConfiguredState->getValue() !== '1')
        ) {
            $signupState = $this->stateRepository->getByNameAndStore('signup_request', 0);

            if ($signupState->getId() !== null && $signupState->getValue() !== 'complete') {
                $response = $this->requestStatus->checkStatus();
                if ($response['complete'] === true) {
                    $this->requestProcess->process($response['response']);
                    $result['success'] = true;
                } elseif ($response['error']) {
                    $this->logger->error('PureClarity Setup Error: ' . $response['error']);
                }
            }
        }
    }
}
