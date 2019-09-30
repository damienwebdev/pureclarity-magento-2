<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;

/**
 * Class Stores
 *
 * Stores ViewModel for Dashboard page
 */
class Stores implements ArgumentInterface
{
    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var StoreInterface $defaultStore */
    private $defaultMagentoStore;

    /** @var integer $defaultPureClarityStoreId */
    private $defaultPureClarityStoreId;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StateRepositoryInterface $stateRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StateRepositoryInterface $stateRepository
    ) {
        $this->storeManager    = $storeManager;
        $this->stateRepository = $stateRepository;
    }

    /**
     * Gets the default magento store
     *
     * @return StoreInterface|null
     */
    public function getMagentoDefaultStore()
    {
        if ($this->defaultMagentoStore === null) {
            $this->defaultMagentoStore = $this->storeManager->getDefaultStoreView();
        }

        return $this->defaultMagentoStore;
    }

    /**
     * Gets the id of the default store for pureclarity
     *
     * @return integer
     */
    public function getPureClarityDefaultStore()
    {
        if ($this->defaultPureClarityStoreId === null) {
            $defaultStore = $this->stateRepository->getByNameAndStore('default_store', 0);
            $storeId = (int)$defaultStore->getValue();
            if (empty($storeId)) {
                $storeId = (int)$this->getMagentoDefaultStore()->getId();
            }
            $this->defaultPureClarityStoreId = $storeId;
        }

        return $this->defaultPureClarityStoreId;
    }

    /**
     * Gets list of stores for display
     *
     * @return integer
     */
    public function hasMultipleStores()
    {
        return $this->storeManager->hasSingleStore() === false;
    }

    /**
     * Gets list of stores for display
     *
     * @return string[]
     */
    public function getStoreList()
    {
        $options = [];

        foreach ($this->storeManager->getStores() as $store) {
            $options[(int)$store->getId()] = $store->getName();
        }

        return $options;
    }
}
