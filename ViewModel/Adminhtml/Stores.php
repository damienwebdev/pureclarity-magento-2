<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;

/**
 * Class Stores
 *
 * Stores ViewModel for Dashboard page
 */
class Stores
{
    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var RequestInterface $request */
    private $request;

    /** @var StoreInterface $selectedStore */
    private $selectedStore;

    /** @var StoreInterface $defaultStore */
    private $defaultMagentoStore;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StateRepositoryInterface $stateRepository
     * @param RequestInterface $request
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StateRepositoryInterface $stateRepository,
        RequestInterface $request
    ) {
        $this->storeManager    = $storeManager;
        $this->stateRepository = $stateRepository;
        $this->request         = $request;
    }

    /**
     * Gets the current store id
     *
     * @return int
     */
    public function getStoreId()
    {
        $store = $this->getStore();
        if ($store) {
            return (int)$store->getId();
        }
        return 0;
    }

    /**
     * Gets the current chosen store
     *
     * @return StoreInterface
     */
    public function getStore()
    {
        if ($this->selectedStore === null) {
            $storeId = $this->request->getParam('store');
            if ($storeId) {
                try {
                    $this->selectedStore = $this->storeManager->getStore($storeId);
                } catch (NoSuchEntityException $e) {

                }
            }
        }

        if (!$this->selectedStore) {
            return $this->getMagentoDefaultStore();
        }

        return $this->selectedStore;
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
