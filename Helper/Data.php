<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\FileFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 *
 * Helper class for core functionality.
 */
class Data
{
    const CURRENT_VERSION = '2.1.0';

    // ENDPOINTS
    protected $scriptUrl = '//pcs.pureclarity.net';
    protected $regions = [
        1 => "https://api-eu-w-1.pureclarity.net",
        2 => "https://api-eu-w-2.pureclarity.net",
        3 => "https://api-eu-c-1.pureclarity.net",
        4 => "https://api-us-e-1.pureclarity.net",
        5 => "https://api-us-e-2.pureclarity.net",
        6 => "https://api-us-w-1.pureclarity.net",
        7 => "https://api-us-w-2.pureclarity.net",
        8 => "https://api-ap-s-1.pureclarity.net",
        9 => "https://api-ap-ne-1.pureclarity.net",
        10 => "https://api-ap-ne-2.pureclarity.net",
        11 => "https://api-ap-se-1.pureclarity.net",
        12 => "https://api-ap-se-2.pureclarity.net",
        13 => "https://api-ca-c-1.pureclarity.net",
        14 => "https://api-sa-e-1.pureclarity.net"
    ];

    protected $sftpRegions = [
        1 => "https://sftp-eu-w-1.pureclarity.net",
        2 => "https://sftp-eu-w-2.pureclarity.net",
        3 => "https://sftp-eu-c-1.pureclarity.net",
        4 => "https://sftp-us-e-1.pureclarity.net",
        5 => "https://sftp-us-e-2.pureclarity.net",
        6 => "https://sftp-us-w-1.pureclarity.net",
        7 => "https://sftp-us-w-2.pureclarity.net",
        8 => "https://sftp-ap-s-1.pureclarity.net",
        9 => "https://sftp-ap-ne-1.pureclarity.net",
        10 => "https://sftp-ap-ne-2.pureclarity.net",
        11 => "https://sftp-ap-se-1.pureclarity.net",
        12 => "https://sftp-ap-se-2.pureclarity.net",
        13 => "https://sftp-ca-c-1.pureclarity.net",
        14 => "https://sftp-sa-e-1.pureclarity.net"
    ];

    const PLACEHOLDER_UPLOAD_DIR = "pureclarity";
    const PROGRESS_FILE_BASE_NAME = 'pureclarity-feed-progress-';
    const PURECLARITY_EXPORT_URL = 'pureclarity/export/feed?storeid={storeid}&type={type}';

    /** @var ScopeConfigInterface $scopeConfig */
    private $scopeConfig;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var Session $checkoutSession */
    private $checkoutSession;

    /** @var FileFactory $ioFileFactory */
    private $ioFileFactory;

    /** @var DirectoryList $directoryList */
    private $directoryList;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Session $checkoutSession
     * @param FileFactory $ioFileFactory
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        FileFactory $ioFileFactory,
        DirectoryList $directoryList
    ) {
        $this->ioFileFactory   = $ioFileFactory;
        $this->scopeConfig     = $scopeConfig;
        $this->storeManager    = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->directoryList   = $directoryList;
    }
    
    // Environment Variables
    public function isActive($storeId)
    {
        $accessKey = $this->getAccessKey($storeId);
        if ($accessKey != null && $accessKey != "") {
            return $this->scopeConfig->getValue(
                "pureclarity/environment/active",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return false;
    }

    public function seoSearchFriendly($storeId = null)
    {
        if ($this->isActive($this->getStoreId($storeId))) {
            return $this->scopeConfig->getValue(
                "pureclarity/advanced/seo_friendly",
                ScopeInterface::SCOPE_STORE,
                $this->getStoreId($storeId)
            );
        }
        return false;
    }

    public function getAdminUrl()
    {
        return "https://admin.pureclarity.net";
    }

    // Credentials
    public function getAccessKey($storeId)
    {
        return $this->scopeConfig->getValue(
            "pureclarity/credentials/access_key",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getSecretKey($storeId)
    {
        return $this->scopeConfig->getValue(
            "pureclarity/credentials/secret_key",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getRegion($storeId)
    {
        $region = $this->scopeConfig->getValue(
            "pureclarity/credentials/region",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($region == null) {
            $region = 1;
        }
        return $region;
    }
    
    public function isFeedNotificationActive($storeId)
    {
        if ($this->isActive($storeId)) {
            return $this->scopeConfig->getValue(
                "pureclarity/feeds/notify_feed",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return false;
    }

    public function isProductIndexingEnabled($storeId)
    {
        if ($this->isActive($storeId)) {
            return $this->scopeConfig->getValue(
                "pureclarity/feeds/product_index",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return false;
    }

    public function isBrandFeedEnabled($storeId)
    {
        if ($this->isActive($storeId)) {
            return $this->scopeConfig->getValue(
                "pureclarity/feeds/brand_feed_enabled",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return false;
    }

    public function getBrandParentCategory($storeId)
    {
        return $this->scopeConfig->getValue(
            "pureclarity/feeds/brand_parent_category",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    // Placeholders
    public function getProductPlaceholderUrl($storeId)
    {
        return $this->scopeConfig->getValue(
            "pureclarity/placeholders/placeholder_product",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCategoryPlaceholderUrl($storeId)
    {
        return $this->scopeConfig->getValue(
            "pureclarity/placeholders/placeholder_category",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getSecondaryCategoryPlaceholderUrl($storeId)
    {
        return $this->scopeConfig->getValue(
            "pureclarity/placeholders/placeholder_category_secondary",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getAdminImageUrl($store, $image, $type)
    {
        if (is_string($image)) {
            $base = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return $base . 'catalog/' . $type . '/' . $image;
        }
        return "";
    }

    public function getAdminImagePath($store, $image, $type)
    {
        if (is_string($image)) {
            $base = $this->directoryList->getPath('media');
            return $base . DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $image;
        }
        return "";
    }

    // ADVANCED
    public function isBMZDebugActive($storeId = null)
    {
        return $this->scopeConfig->getValue(
            "pureclarity/advanced/bmz_debug",
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId($storeId)
        );
    }

    // END POINTS
    public function getHost($storeId)
    {
        $pureclarityHostEnv = getenv('PURECLARITY_MAGENTO_HOST');
        if ($pureclarityHostEnv != null && $pureclarityHostEnv != '') {
            $parsed = parse_url($pureclarityHostEnv);
            if (empty($parsed['scheme'])) {
                $pureclarityHostEnv = 'http://' . $pureclarityHostEnv;
            }
            return $pureclarityHostEnv;
        }
        $region = $this->getRegion($storeId);
        return $this->regions[$region];
    }

    public function getSftpHost($storeId)
    {
        $pureclarityHostEnv = getenv('PURECLARITY_SFTP_HOST');
        if ($pureclarityHostEnv != null && $pureclarityHostEnv != '') {
            return $pureclarityHostEnv;
        }
        $region = $this->getRegion($storeId);
        return $this->sftpRegions[$region];
    }

    public function getSftpPort($storeId)
    {
        $pureclarityHostEnv = getenv('PURECLARITY_SFTP_PORT');
        if ($pureclarityHostEnv != null && $pureclarityHostEnv != '') {
            return intval($pureclarityHostEnv);
        }
        return 2222;
    }

    public function useSSL($storeId)
    {
        $pureclarityHostEnv = getenv('PURECLARITY_MAGENTO_USESSL');
        if ($pureclarityHostEnv != null && strtolower($pureclarityHostEnv) == 'false') {
            return false;
        }
        return true;
    }

    public function getServerSideEndpoint($storeId)
    {
        return $this->getHost($storeId) . '/api/serverside';
    }

    public function getDeltaEndpoint($storeId)
    {
        return $this->getHost($storeId) . '/api/productdelta';
    }

    public function getFeedBaseUrl($storeId)
    {
        $url = getenv('PURECLARITY_FEED_HOST');
        $port = getenv('PURECLARITY_FEED_PORT');
        if (empty($url)) {
            $url = $this->sftpRegions[$this->getRegion($storeId)];
        }
        if (! empty($port)) {
            $url = $url . ":" . $port;
        }

        return $url . "/";
    }

    public function getFeedNotificationEndpoint($storeId, $websiteDomain, $feedType)
    {
        $returnUrl = $websiteDomain . '/' . self::PURECLARITY_EXPORT_URL;
        $returnUrl = str_replace('{storeid}', $storeId, $returnUrl);
        $returnUrl = str_replace('{type}', $feedType, $returnUrl);
        return $this->getHost($storeId)
                . '/api/productfeed?appkey='
                . $this->getAccessKey($storeId)
                . '&url='. urlencode($returnUrl)
                . '&feedtype=magentoplugin1.0.0';
    }

    public function getFeedBody($storeId)
    {
        $body = [
            "AccessKey" => $this->getAccessKey($storeId),
            "SecretKey" => $this->getSecretKey($storeId)
        ];
        return $this->coreHelper->formatFeed($body);
    }

    public function getFileNameForFeed($feedtype, $storeCode)
    {
        if ($feedtype == "orders") {
            return $storeCode . "-orders.csv";
        }
        return $storeCode . "-" . $feedtype . ".json";
    }

    // MISC/HELPER METHODS
    public function getScriptUrl()
    {
        return $this->scriptUrl;
    }

    public function getApiStartUrl()
    {
        $pureclarityScriptUrl = getenv('PURECLARITY_SCRIPT_URL');
        if ($pureclarityScriptUrl != null && $pureclarityScriptUrl != '') {
            $pureclarityScriptUrl .= $this->getAccessKey($this->getStoreId()) . '/dev.js';
            return $pureclarityScriptUrl;
        }
        return $this->getScriptUrl() . '/' . $this->getAccessKey($this->getStoreId()) . '/cs.js';
    }

    public function getStoreId($storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        return $storeId;
    }

    public function getCurrentUrl()
    {
        return $this->storeManager->getStore()->getCurrentUrl();
    }

    public function getPlaceholderDir()
    {
        return $this->directoryList->getPath('media')
                . DIRECTORY_SEPARATOR . self::PLACEHOLDER_UPLOAD_DIR . DIRECTORY_SEPARATOR;
    }

    public function getPlaceholderUrl($store)
    {
        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product';
    }

    public function getPureClarityBaseDir()
    {
        $varDir = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR  . 'pureclarity';
        $fileIo = $this->ioFileFactory->create();
        $fileIo->mkdir($varDir);
        return $varDir;
    }

    public function getProgressFileName()
    {
        return $this->getPureClarityBaseDir() . DIRECTORY_SEPARATOR . self::PROGRESS_FILE_BASE_NAME . 'all.json';
    }

    public function setProgressFile(
        $progressFileName,
        $feedName,
        $currentPage,
        $pages,
        $isComplete = "false",
        $isUploaded = "false",
        $error = ""
    ) {
        if ($progressFileName != null) {
            $progressFile = fopen($progressFileName, "w");
            fwrite(
                $progressFile,
                "{\"name\":\"$feedName\",\"cur\":$currentPage,\"max\":$pages,\"isComplete\":$isComplete,"
                . "\"isUploaded\":$isUploaded,\"error\":\"$error\"}"
            );
            fclose($progressFile);
        }
    }

    public function getDOMSelector($storeId = null)
    {
        $selector = $this->scopeConfig->getValue(
            "pureclarity/advanced/pureclarity_search_selector",
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId($storeId)
        );
        if ($selector && $selector != "") {
            return $selector;
        }
        return ".columns";
    }

    public function getOrderObject()
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    public function getOrderForTracking($lastOrder = null)
    {
        if (!$lastOrder) {
            $lastOrder = $this->getOrderObject();
        }
        
        if (!$lastOrder) {
            return null;
        }

        $order = [
            "orderid" => $lastOrder['increment_id'],
            "firstname" => $lastOrder['customer_firstname'],
            "lastname" => $lastOrder['customer_lastname'],
            "postcode" => $lastOrder->getShippingAddress()['postcode'],
            "userid" => $lastOrder['customer_id'],
            "groupid" => $lastOrder['customer_group_id'],
            "ordertotal" => $lastOrder['grand_total']
        ];

        $orderItems = [];
        $visibleItems = $lastOrder->getAllVisibleItems();
        $allItems = $lastOrder->getAllItems();
        $count = 0;

        foreach ($visibleItems as $item) {
            $count++;

            $orderItems[$item->getItemId()] = [
                "id$count" => $item->getProductId(),
                "refid$count" => $item->getItemId(),
                "qty$count" => $item->getQtyOrdered(),
                "unitprice$count" => $item->getPrice(),
                "children$count" => []
            ];

            foreach ($allItems as $childItem) {
                $parentId = $childItem->getParentItemId();
                if ($parentId && isset($orderItems[$parentId])) {
                    $orderItems[$parentId]['children' . $count][] = [
                        "sku" => $childItem->getSku(),
                        "qty" => $childItem->getQtyOrdered()
                    ];
                }
            }
        }

        $order['productcount'] = $count;

        foreach ($orderItems as $item) {
            foreach ($item as $key => $value) {
                $order[$key] = $value;
            }
        }

        return $order;
    }

    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    public function getNumberSwatchesPerProduct($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'catalog/frontend/swatches_per_product',
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId($storeId)
        );
    }

    public function showSwatches($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'catalog/frontend/show_swatches_in_product_list',
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId($storeId)
        );
    }

    public function formatFeed($feed, $feedFormat = 'json')
    {
        switch ($feedFormat) {
            case 'json':
                return json_encode($feed);
                break;
            case 'jsonpretty':
                return json_encode($feed, JSON_PRETTY_PRINT);
                break;
        }
    }
}
