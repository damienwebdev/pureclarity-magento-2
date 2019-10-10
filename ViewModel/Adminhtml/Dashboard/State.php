<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Pureclarity\Core\Api\Data\StateInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;

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

    /** @var string $newVersion */
    private $newVersion;

    /** @var bool $isNotConfigured */
    private $isNotConfigured;

    /** @var bool $signupStarted */
    private $signupStarted;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var ProductMetadataInterface $productMetadata */
    private $productMetadata;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        ProductMetadataInterface $productMetadata
    ) {
        $this->stateRepository = $stateRepository;
        $this->productMetadata = $productMetadata;
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
     * Returns whether the PureClarity module is up to date
     *
     * @return boolean
     */
    public function isUpToDate()
    {
        $newVersion = $this->getNewVersion();
        return ($newVersion === '' || version_compare($newVersion, Data::CURRENT_VERSION, '<='));
    }

    /**
     * Returns the current plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return Data::CURRENT_VERSION;
    }

    /**
     * Returns the latest version of the plugin available
     *
     * @return bool
     */
    public function getNewVersion()
    {
        if ($this->newVersion === null) {
            /** @var StateInterface $state */
            $state = $this->stateRepository->getByNameAndStore('new_version', 0);
            $this->newVersion = ($state->getId()) ? $state->getValue() : '';
        }

        return $this->newVersion;
    }

    /**
     * Returns the current Magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion() . ' ' . $this->productMetadata->getEdition();
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
            $this->signupStarted = ($state->getId() !== null);
        }

        return $this->signupStarted;
    }
}
