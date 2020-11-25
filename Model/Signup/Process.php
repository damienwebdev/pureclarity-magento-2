<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Signup;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\Exception\CouldNotDeleteException;
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
        $result = [
            'errors' => []
        ];

        try {
            $this->saveConfig($requestData);
            $this->setWelcomeState('auto');
            $this->completeSignup();
            $this->triggerFeeds($requestData);
        } catch (CouldNotSaveException $e) {
            $result['errors'][] = __('Error processing request: %1', $e->getMessage());
        }

        return $result;
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

        $result['errors'] = $this->validateManualConfigure($requestData);

        if (empty($result['errors'])) {
            try {
                $this->saveConfig($requestData);
                $this->setWelcomeState('manual');
                $this->triggerFeeds($requestData);
            } catch (CouldNotSaveException $e) {
                $result['errors'][] = __('Error processing request: %1', $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Validates the params in the manual configure request
     *
     * @param mixed[] $requestData
     * @return array
     */
    private function validateManualConfigure($requestData)
    {
        $errors = [];

        if (!isset($requestData['access_key']) || empty($requestData['access_key'])) {
            $errors[] = __('Missing Access Key');
        }

        if (!isset($requestData['secret_key']) || empty($requestData['secret_key'])) {
            $errors[] = __('Missing Secret Key');
        }

        if (!isset($requestData['region']) || empty($requestData['region'])) {
            $errors[] = __('Missing Region');
        }

        if (!isset($requestData['store_id'])) {
            $errors[] = __('Missing Store ID');
        }

        return $errors;
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
        $this->coreConfig->setDeltasEnabled(1, (int)$requestData['store_id']);
        $this->coreConfig->setIsDailyFeedActive(1, (int)$requestData['store_id']);
        $this->cacheManager->clean([CacheTypeConfig::TYPE_IDENTIFIER]);
    }

    /**
     * Saves the is_configured flag
     *
     * @param string $type
     * @return void
     * @throws CouldNotSaveException
     */
    private function setWelcomeState($type)
    {
        $state = $this->stateRepository->getByNameAndStore('show_welcome_banner', 0);
        if ($type === 'manual') {
            $state->setName('show_manual_welcome_banner');
        } else {
            $state->setName('show_welcome_banner');
        }

        $state->setValue('1');
        $state->setStoreId(0);
        $this->stateRepository->save($state);
    }

    /**
     * Updates the signup request to be complete
     *
     * @return void
     * @throws CouldNotDeleteException
     */
    private function completeSignup()
    {
        $state = $this->stateRepository->getByNameAndStore('signup_request', 0);
        if ($state->getId()) {
            $this->stateRepository->delete($state);
        }
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
            'user',
            'orders'
        ];

        $storeId = (int)$requestData['store_id'];
        if ($storeId === 0) {
            $store = $this->storeManager->getDefaultStoreView();
            if ($store) {
                $storeId = (int)$store->getId();
            }
        }

        $cronFeed->scheduleSelectedFeeds($storeId, $feeds);
    }
}
