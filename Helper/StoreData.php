<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class StoreData
 *
 * Helps with getting store-specific data (URL, store name etc)
 */
class StoreData
{
    const CONFIG_PATH_DEFAULT_CURRENCY = 'currency/options/default';
    const CONFIG_PATH_UNSECURE_BASE_URL = 'web/unsecure/base_url';
    const CONFIG_PATH_SECURE_BASE_URL = 'web/secure/base_url';
    const CONFIG_PATH_SECURE_IN_FRONTEND = 'web/secure/use_in_frontend';
    const CONFIG_PATH_TIMEZONE = 'general/locale/timezone';
    
    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig  = $scopeConfig;
    }

    /**
     * Gets list of stores for display
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getStoreURL($storeId)
    {
        $secure = $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_SECURE_IN_FRONTEND,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $path = $secure
            ? self::CONFIG_PATH_SECURE_BASE_URL
            : self::CONFIG_PATH_UNSECURE_BASE_URL;

        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Gets list of stores for display
     *
     * @return string
     */
    public function getStoreCurrency($storeId)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_DEFAULT_CURRENCY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Gets list of stores for display
     *
     * @return integer
     */
    public function getStoreTimezone($storeId)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_TIMEZONE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
