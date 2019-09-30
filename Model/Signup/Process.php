<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Signup;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Cron;
use Pureclarity\Core\Model\CronFactory;

/**
 * Class Process
 *
 * model for processing signup requests
 */
class Process
{
    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var CronFactory $cronFactory */
    private $cronFactory;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var Manager $cacheManager */
    private $cacheManager;

    /**
     * @param StateRepositoryInterface $stateRepository
     * @param CoreConfig $coreConfig
     * @param CronFactory $cronFactory
     * @param StoreManagerInterface $storeManager
     * @param Manager $cacheManager
     */
    public function __construct(
        StateRepositoryInterface $stateRepository,
        CoreConfig $coreConfig,
        CronFactory $cronFactory,
        StoreManagerInterface $storeManager,
        Manager $cacheManager
    ) {
        $this->stateRepository = $stateRepository;
        $this->coreConfig      = $coreConfig;
        $this->cronFactory     = $cronFactory;
        $this->storeManager    = $storeManager;
        $this->cacheManager    = $cacheManager;
    }

    /**
     * Processes the signup request
     *
     * @param mixed[] $requestData
     *
     * @return mixed[]
     */
    public function process($requestData)
    {
        $this->saveConfig($requestData);
        $this->setConfiguredState();
        $this->completeSignup();
        $this->setDefaultStore((int)$requestData['store_id']);
        $this->triggerFeeds($requestData);

        return [];
    }

    /**
     * Processes a manual configuration from the dashboard page
     *
     * @param mixed[] $requestData
     *
     * @return mixed[]
     */
    public function processManualConfigure($requestData)
    {
        $result = [
            'errors' => []
        ];

        if (!isset($requestData['access_key']) || empty($requestData['access_key'])) {
            $result['errors'][] = __('Missing Access Key');
        }

        if (!isset($requestData['secret_key']) || empty($requestData['secret_key'])) {
            $result['errors'][] = __('Missing Secret Key');
        }

        if (!isset($requestData['region']) || empty($requestData['region'])) {
            $result['errors'][] = __('Missing Region');
        }

        if (!isset($requestData['store_id'])) {
            $result['errors'][] = __('Missing Store ID');
        }

        if (empty($result['errors'])) {
            $this->saveConfig($requestData);
            $this->setConfiguredState();
            $this->setDefaultStore((int)$requestData['store_id']);
            $this->triggerFeeds($requestData);
        }

        return $result;
    }

    /**
     * Saves the PureClarity credentials to the Magento config
     *
     * @param mixed[] $requestData
     */
    private function saveConfig($requestData)
    {
        $this->coreConfig->setAccessKey($requestData['access_key'], (int)$requestData['store_id']);
        $this->coreConfig->setSecretKey($requestData['secret_key'], (int)$requestData['store_id']);
        $this->coreConfig->setRegion($requestData['region'], (int)$requestData['store_id']);
        $this->coreConfig->setIsActive(1, (int)$requestData['store_id']);
        $this->cacheManager->clean([CacheTypeConfig::TYPE_IDENTIFIER]);
    }

    /**
     * Saves the is_configured flag
     *
     * @return bool
     */
    private function setConfiguredState()
    {
        $state = $this->stateRepository->getByNameAndStore('is_configured', 0);
        $state->setName('is_configured');
        $state->setValue('1');
        $state->setStoreId(0);

        try {
            $this->stateRepository->save($state);
            $saved = true;
        } catch (CouldNotSaveException $e) {
            $saved = false;
        }

        return $saved;
    }

    /**
     * Updates the signup request to be complete
     *
     * @return bool
     */
    private function completeSignup()
    {
        $state = $this->stateRepository->getByNameAndStore('signup_request', 0);
        $state->setName('signup_request');
        $state->setValue('complete');
        $state->setStoreId(0);

        try {
            $this->stateRepository->save($state);
            $saved = true;
        } catch (CouldNotSaveException $e) {
            $saved = false;
        }

        return $saved;
    }

    /**
     * Saves the signup store as the default store (so dashboard load right store)
     *
     * @param integer $storeId
     *
     * @return bool
     */
    private function setDefaultStore($storeId)
    {
        $state = $this->stateRepository->getByNameAndStore('default_store', 0);
        $state->setName('default_store');
        $state->setValue($storeId);
        $state->setStoreId(0);

        try {
            $this->stateRepository->save($state);
            $saved = true;
        } catch (CouldNotSaveException $e) {
            $saved = false;
        }

        return $saved;
    }

    /**
     * Triggers a run of all feeds
     *
     * @param mixed[] $requestData
     */
    private function triggerFeeds($requestData)
    {
        /** @var Cron $cronFeed */
        $cronFeed = $this->cronFactory->create();
        $feeds = [
            'product',
            'category',
            'brand',
            'user'
        ];

        $storeId = (int)$requestData['store_id'];
        if ($storeId === 0) {
            $store = $this->storeManager->getDefaultStoreView();
            if ($store) {
                $storeId = $store->getId();
            }
        }

        $cronFeed->scheduleSelectedFeeds($storeId, $feeds);
    }
}
