<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Pureclarity\Core\Api\Data\StateInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class State
 *
 * Dashboard State ViewModel
 */
class State
{
    const STATE_NOT_CONFIGURED = 'not_configured';
    const STATE_WAITING = 'waiting';
    const STATE_CONFIGURED = 'configured';

    /** @var string $newVersion */
    private $newVersion;

    /** @var bool $waiting */
    private $waiting;

    /** @var bool $configured */
    private $configured;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var ProductMetadataInterface $productMetadata */
    private $productMetadata;

    /** @var RequestInterface $request */
    private $request;

    /** @var CoreConfig $request */
    private $coreConfig;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param ProductMetadataInterface $productMetadata
     * @param RequestInterface $request
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        ProductMetadataInterface $productMetadata,
        RequestInterface $request,
        CoreConfig $coreConfig
    ) {
        $this->stateRepository = $stateRepository;
        $this->productMetadata = $productMetadata;
        $this->request         = $request;
        $this->coreConfig      = $coreConfig;
    }

    /**
     * Returns whether the dashboard should show the not configured state
     *
     * @return string
     */
    public function getStateName($storeId)
    {
        if ($this->isConfigured($storeId)) {
            return self::STATE_CONFIGURED;
        }

        if ($this->isWaiting($storeId)) {
            return self::STATE_WAITING;
        }

        return self::STATE_NOT_CONFIGURED;
    }

    /**
     * Returns whether the dashboard should show the waiting for sign up to finish state
     *
     * @return boolean
     */
    public function isWaiting($storeId)
    {
        if ($this->waiting === null) {
            /** @var StateInterface $state */
            $state = $this->stateRepository->getByNameAndStore('signup_request', $storeId);
            $this->waiting = ($state->getId() !== null);
        }

        return $this->waiting;
    }

    /**
     * Returns whether the dashboard should show the not configured state
     *
     * @return boolean
     */
    public function isConfigured($storeId)
    {
        if ($this->configured === null) {
            $accessKey = $this->coreConfig->getAccessKey($storeId);
            $secretKey = $this->coreConfig->getSecretKey($storeId);
            $this->configured = ($accessKey && $secretKey);
        }

        return $this->configured;
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
     * @return string
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
}
