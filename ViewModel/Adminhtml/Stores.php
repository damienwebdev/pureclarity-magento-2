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
use Psr\Log\LoggerInterface;

/**
 * Class Stores
 *
 * Stores ViewModel for Dashboard page
 */
class Stores
{
    /** @var StoreInterface $selectedStore */
    private $selectedStore;

    /** @var StoreInterface $defaultStore */
    private $defaultMagentoStore;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var RequestInterface $request */
    private $request;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->request      = $request;
        $this->logger       = $logger;
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
                    $this->logger->error('PureClarity: Admin Dashboard could not load selected store');
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
}
