<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * class Stores
 *
 * Stores ViewModel for Dashboard page
 */
class Stores implements ArgumentInterface
{
    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var StoreInterface $defaultStore */
    private $defaultStore;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Gets list of stores for display
     *
     * @return StoreInterface|null
     */
    public function getDefaultStore()
    {
        if ($this->defaultStore === null) {
            $this->defaultStore = $this->storeManager->getDefaultStoreView();
        }

        return $this->defaultStore;
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

        $options[Store::DEFAULT_STORE_ID] = __('All Store Views');

        foreach ($this->storeManager->getStores() as $store) {
            $options[$store->getId()] = $store->getName();
        }

        return $options;
    }
}
