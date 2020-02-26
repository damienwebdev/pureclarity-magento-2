<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Serverside\Data;

use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Psr\Log\LoggerInterface;

/**
 * Serverside Store information handler, gets information from the store
 */
class Store
{
    /** @var LoggerInterface */
    private $logger;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CoreConfig */
    private $coreConfig;

    /**
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        CoreConfig $coreConfig
    ) {
        $this->logger       = $logger;
        $this->storeManager = $storeManager;
        $this->coreConfig   = $coreConfig;
    }

    /**
     * Gets the store's application access key
     *
     * @param string $storeId
     * @return string
     */
    public function getAccessKey($storeId)
    {
        return $this->coreConfig->getAccessKey($storeId);
    }

    /**
     * Gets the store's application secret key
     *
     * @param string $storeId
     * @return string
     */
    public function getSecretKey($storeId)
    {
        return $this->coreConfig->getSecretKey($storeId);
    }

    /**
     * Gets the store's currency
     *
     * @param $storeId
     * @return string
     */
    public function getCurrency($storeId)
    {
        $code = '';
        try {
            $code = $this->storeManager->getStore($storeId)->getCurrentCurrency()->getCode();
        } catch (\Exception $e) {
            $this->logger->error('PureClarity ERROR when getting currency code: ' . $e->getMessage());
        }

        return $code;
    }
}
