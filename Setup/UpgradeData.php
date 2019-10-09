<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Setup;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class UpgradeSchema
 *
 * Runs upgrades to Schema based on PureClarity module version
 */
class UpgradeData implements UpgradeDataInterface
{
    /** @var CoreConfig */
    private $coreConfig;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param CoreConfig $coreConfig
     * @param StateRepositoryInterface $stateRepository
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        CoreConfig $coreConfig,
        StateRepositoryInterface $stateRepository,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->coreConfig      = $coreConfig;
        $this->stateRepository = $stateRepository;
        $this->storeManager    = $storeManager;
        $this->logger          = $logger;
    }

    /**
     * Checks to see if a version change needs to trigger an upgrade
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $setup->startSetup();
            $this->checkForPreviousInstall();
            $setup->endSetup();
        }
    }

    /**
     * Populates data in the pureclarity_state table if already configured
     *
     * @return void
     */
    private function checkForPreviousInstall()
    {
        $stores = $this->storeManager->getStores();
        $configured = false;
        $configuredStoreId = null;
        foreach ($stores as $store) {
            $accessKey = $this->coreConfig->getAccessKey($store->getId());
            if ($accessKey) {
                $configured = true;
                $configuredStoreId = $store->getId();
                break;
            }
        }

        if ($configured && $configuredStoreId !== null) {
            try {
                $configuredState = $this->stateRepository->getByNameAndStore('is_configured', 0);
                $configuredState->setName('is_configured');
                $configuredState->setValue(1);
                $configuredState->setStoreId(0);
                $this->stateRepository->save($configuredState);

                $defaultStoreState = $this->stateRepository->getByNameAndStore('default_store', 0);
                $defaultStoreState->setName('default_store');
                $defaultStoreState->setValue($configuredStoreId);
                $defaultStoreState->setStoreId(0);
                $this->stateRepository->save($defaultStoreState);
            } catch (CouldNotSaveException $e) {
                $this->logger->error('PureClarity: could not set state on upgrade: ' . $e->getMessage());
            }

        }
    }
}
