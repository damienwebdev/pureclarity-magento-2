<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Pureclarity\Core\Api\Data\StateInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;

/**
 * Class State
 *
 * Dashboard State ViewModel
 */
class State implements ArgumentInterface
{
    const STATE_NOT_CONFIGURED = 'not_configured';
    const STATE_WAITING = 'waiting';
    const STATE_CONFIGURED = 'configured';

    /** @var bool $isNotConfigured */
    private $isNotConfigured;

    /** @var bool $signupStarted */
    private $signupStarted;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /**
     * @param StateRepositoryInterface $stateRepository
     */
    public function __construct(
        StateRepositoryInterface $stateRepository
    ) {
        $this->stateRepository = $stateRepository;
    }

    /**
     * Returns whether the dashboard should show the not configured state
     *
     * @return boolean
     */
    public function getStateName()
    {
        if ($this->isNotConfigured()) {
            return self::STATE_NOT_CONFIGURED;
        } elseif ($this->isWaiting()) {
            return self::STATE_WAITING;
        } else {
            return self::STATE_CONFIGURED;
        }
    }

    /**
     * Returns whether the dashboard should show the not configured state
     *
     * @return boolean
     */
    public function isNotConfigured()
    {
        return ($this->getIsNotConfigured() === true && $this->getSignupStarted() === false);
    }

    /**
     * Returns whether the dashboard should show the waiting for sign up to finish state
     *
     * @return boolean
     */
    public function isWaiting()
    {
        return ($this->getIsNotConfigured() === true && $this->getSignupStarted() === true);
    }

    /**
     * @return bool
     */
    private function getIsNotConfigured()
    {
        if ($this->isNotConfigured === null) {
            /** @var StateInterface $state */
            $state = $this->stateRepository->getByNameAndStore('is_configured', 0);
            $this->isNotConfigured = ($state->getId() === null || $state->getValue() === '0');
        }

        return $this->isNotConfigured;
    }

    /**
     * @return bool
     */
    private function getSignupStarted()
    {
        if ($this->signupStarted === null) {
            /** @var StateInterface $state */
            $state = $this->stateRepository->getByNameAndStore('signup_request', 0);
            $this->signupStarted = ($state->getId() !== null && $state->getValue() !== 'complete');
        }

        return $this->signupStarted;
    }
}
