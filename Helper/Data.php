<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\FileFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 *
 * Helper class for core functionality.
 */
class Data
{
    const CURRENT_VERSION = '4.0.6';
    const PROGRESS_FILE_BASE_NAME = 'pureclarity-feed-progress-';

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var Session $checkoutSession */
    private $checkoutSession;

    /** @var FileFactory $ioFileFactory */
    private $ioFileFactory;

    /** @var DirectoryList $directoryList */
    private $directoryList;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Session $checkoutSession
     * @param FileFactory $ioFileFactory
     * @param DirectoryList $directoryList
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        FileFactory $ioFileFactory,
        DirectoryList $directoryList
    ) {
        $this->ioFileFactory   = $ioFileFactory;
        $this->storeManager    = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->directoryList   = $directoryList;
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

    // END POINTS
    public function useSSL($storeId)
    {
        $pureclarityHostEnv = getenv('PURECLARITY_MAGENTO_USESSL');
        if ($pureclarityHostEnv != null && strtolower($pureclarityHostEnv) == 'false') {
            return false;
        }
        return true;
    }

    public function getFileNameForFeed($feedtype, $storeCode)
    {
        if ($feedtype == "orders") {
            return $storeCode . "-orders.csv";
        }
        return $storeCode . "-" . $feedtype . ".json";
    }

    // MISC/HELPER METHODS
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

    public function getOrderObject()
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
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
