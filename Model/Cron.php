<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Filesystem;
use Pureclarity\Core\Helper\Serializer;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Helper\Service\Url;
use Pureclarity\Core\Helper\Soap;
use Pureclarity\Core\Model\ResourceModel\ProductFeed\CollectionFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\App\Area;

/**
 * Class Cron
 *
 * Controls the execution of feeds sent to PureClarity.
 */
class Cron
{
    /** @var Soap $coreSoapHelper */
    private $coreSoapHelper;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var CollectionFactory $productFeedCollectionFactory */
    private $productFeedCollectionFactory;

    /** @var ProductExportFactory $productExportFactory */
    private $productExportFactory;

    /** @var ProductFactory $catalogProductFactory */
    private $catalogProductFactory;

    /** @var FeedFactory $coreFeedFactory */
    private $coreFeedFactory;

    /** @var StoreFactory $storeStoreFactory */
    private $storeStoreFactory;

    /** @var Filesystem $fileSystem */
    private $fileSystem;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Url $serviceUrl */
    private $serviceUrl;

    /** @var Emulation $appEmulation */
    private $appEmulation;

    /**
     * @param Soap $coreSoapHelper
     * @param StoreManagerInterface $storeManager
     * @param Data $coreHelper
     * @param CollectionFactory $productFeedCollectionFactory
     * @param ProductExportFactory $productExportFactory
     * @param ProductFactory $catalogProductFactory
     * @param FeedFactory $coreFeedFactory
     * @param StoreFactory $storeStoreFactory
     * @param Filesystem $fileSystem
     * @param StateRepositoryInterface $stateRepository
     * @param Serializer $serializer
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     * @param Url $serviceUrl
     * @param Emulation $appEmulation
     */
    public function __construct(
        Soap $coreSoapHelper,
        StoreManagerInterface $storeManager,
        Data $coreHelper,
        CollectionFactory $productFeedCollectionFactory,
        ProductExportFactory $productExportFactory,
        ProductFactory $catalogProductFactory,
        FeedFactory $coreFeedFactory,
        StoreFactory $storeStoreFactory,
        Filesystem $fileSystem,
        StateRepositoryInterface $stateRepository,
        Serializer $serializer,
        LoggerInterface $logger,
        CoreConfig $coreConfig,
        Url $serviceUrl,
        Emulation $appEmulation
    ) {
        $this->coreSoapHelper               = $coreSoapHelper;
        $this->storeManager                 = $storeManager;
        $this->coreHelper                   = $coreHelper;
        $this->productFeedCollectionFactory = $productFeedCollectionFactory;
        $this->productExportFactory         = $productExportFactory;
        $this->catalogProductFactory        = $catalogProductFactory;
        $this->coreFeedFactory              = $coreFeedFactory;
        $this->storeStoreFactory            = $storeStoreFactory;
        $this->fileSystem                   = $fileSystem;
        $this->stateRepository              = $stateRepository;
        $this->serializer                   = $serializer;
        $this->logger                       = $logger;
        $this->coreConfig                   = $coreConfig;
        $this->serviceUrl                   = $serviceUrl;
        $this->appEmulation                 = $appEmulation;
    }

    // Produce all feeds in one file.
    public function allFeeds($storeId)
    {
        $this->doFeed([
            Feed::FEED_TYPE_PRODUCT,
            Feed::FEED_TYPE_CATEGORY,
            Feed::FEED_TYPE_BRAND,
            Feed::FEED_TYPE_USER
        ], $storeId, $this->getFeedFilePath('all', $storeId));
    }
    
    public function selectedFeeds($storeId, $feeds)
    {
        $this->doFeed($feeds, $storeId, $this->getFeedFilePath('all', $storeId));
    }

    /**
     * Produce a feed and POST to PureClarity.
     * @param $feedTypes array
     * @param $storeId integer
     * @param $feedFilePath
     */
    public function doFeed($feedTypes, $storeId, $feedFilePath)
    {
        $hasOrder = in_array(Feed::FEED_TYPE_ORDER, $feedTypes);
        $isOrderOnly = ($hasOrder && count($feedTypes) == 1);

        $progressFileName = $this->coreHelper->getProgressFileName();
        $feedModel = $this->coreFeedFactory
            ->create()
            ->initialise($storeId, $progressFileName);
        if (! $feedModel) {
            return false;
        }

        $this->logFeedQueue($feedTypes, $storeId);
        $feedsRemaining = $feedTypes;
        // Post the feed data for the specified feed type
        foreach ($feedTypes as $key => $feedType) {
            switch ($feedType) {
                case Feed::FEED_TYPE_PRODUCT:
                    $feedModel->sendProducts();
                    if (($key = array_search(Feed::FEED_TYPE_PRODUCT, $feedsRemaining)) !== false) {
                        unset($feedsRemaining[$key]);
                    }
                    $this->logFeedQueue($feedsRemaining, $storeId);
                    break;
                case Feed::FEED_TYPE_CATEGORY:
                    $feedModel->sendCategories();
                    if (($key = array_search(Feed::FEED_TYPE_CATEGORY, $feedsRemaining)) !== false) {
                        unset($feedsRemaining[$key]);
                    }
                    $this->logFeedQueue($feedsRemaining, $storeId);
                    break;
                case Feed::FEED_TYPE_BRAND:
                    if ($this->coreConfig->isBrandFeedEnabled($storeId)) {
                        $feedModel->sendBrands();
                        if (($key = array_search(Feed::FEED_TYPE_BRAND, $feedsRemaining)) !== false) {
                            unset($feedsRemaining[$key]);
                        }
                        $this->logFeedQueue($feedsRemaining, $storeId);
                    }
                    break;
                case Feed::FEED_TYPE_USER:
                    $feedModel->sendUsers();
                    if (($key = array_search(Feed::FEED_TYPE_USER, $feedsRemaining)) !== false) {
                        unset($feedsRemaining[$key]);
                    }
                    $this->logFeedQueue($feedsRemaining, $storeId);
                    break;
                case Feed::FEED_TYPE_ORDER:
                    $feedModel->sendOrders();
                    if (($key = array_search(Feed::FEED_TYPE_ORDER, $feedsRemaining)) !== false) {
                        unset($feedsRemaining[$key]);
                    }
                    $this->logFeedQueue($feedsRemaining, $storeId);
                    break;
                default:
                    throw new \InvalidArgumentException("PureClarity feed type not recognised: {$feedType}");
            }
        }
        $feedModel->checkSuccess();
        $this->setBannerStatus($storeId);
        $this->removeFeedQueue($storeId);
    }

    // Produce a product feed and notify PureClarity so that it can fetch it.
    public function fullProductFeed($storeId)
    {
        $this->doFeed([
                Feed::FEED_TYPE_PRODUCT
            ], $storeId, $this->getFeedFilePath(Feed::FEED_TYPE_PRODUCT, $storeId));
    }

    // Produce a category feed and notify PureClarity so that it can fetch it.
    public function fullCategoryFeed($storeId)
    {
        $this->doFeed([
                Feed::FEED_TYPE_CATEGORY
            ], $storeId, $this->getFeedFilePath(Feed::FEED_TYPE_CATEGORY, $storeId));
    }

    // Produce a brand feed and notify PureClarity so that it can fetch it.
    public function fullBrandFeed($storeId)
    {
        $this->doFeed([
                Feed::FEED_TYPE_BRAND
            ], $storeId, $this->getFeedFilePath(Feed::FEED_TYPE_BRAND, $storeId));
    }

    private function getFeedFilePath($feedType, $storeId)
    {
        $store = $this->storeStoreFactory->create()->load($storeId);
        return $this->coreHelper->getPureClarityBaseDir()
                . DIRECTORY_SEPARATOR
                . $this->coreHelper->getFileNameForFeed($feedType, $store->getCode());
    }

    /**
     * Reindexes products, called via cron every minute (see /etc/crontab.xml)
     */
    public function reindexData()
    {
        $this->logger->debug('PureClarity: Reindexing');
        // create a unique token until we get a response from PureClarity
        $uniqueId = 'PureClarity' . uniqid();
        $requests = [];

        $collection = $this->productFeedCollectionFactory->create()
                         ->addFieldToFilter('status_id', ['eq' => 0]);
 
        // Loop round each store and process Deltas
        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                foreach ($group->getStores() as $store) {
                    // Check we're allowed to do it for this store
                    if ($this->coreConfig->areDeltasEnabled($store->getId())) {
                        $this->logger->debug('PureClarity: Checking Reindex for StoreID: ' . $store->getId());
                    
                        $deleteProducts = $feedProducts = [];
                        
                        // Check we have something
                        if ($collection->count() > 0) {
                            // emulate frontend so product images work correctly
                            $this->appEmulation->startEnvironmentEmulation($store->getId(), Area::AREA_FRONTEND, true);

                            $reindexTasks = [];
                            $productHash = [];

                            foreach ($collection as $deltaProduct) {
                                if ($deltaProduct->getProductId() == -1) {
                                    // Full Feed
                                    $task = $deltaProduct->getToken();
                                    if (!in_array($task, $reindexTasks)) {
                                        $reindexTasks[] = $task;
                                    }
                                    $deltaProduct->setStatusId(3)->save();
                                } else {
                                    // park these so that another process doesn't pick them up, also
                                    // create a hash to get last value (in case product been edited multiple times)
                                    $productHash[$deltaProduct->getProductId()] = $deltaProduct;
                                }
                            }

                            // Process any deltas
                            if (count($productHash) > 0) {
                                $productExportModel = $this->productExportFactory->create();
                                
                                $productExportModel->init($store->getId());
                                
                                // load products
                                foreach ($productHash as $deltaProduct) {
                                    // Get product for this store
                                    $product = $this->catalogProductFactory->create()
                                        ->setStoreId($store->getId())
                                        ->load($deltaProduct->getProductId());
                                            
                                    // Check product is loaded
                                    if ($product != null) {
                                        // Is deleted?
                                        $deleted = $product->getId() === null ||
                                                $product->getData('status') == Status::STATUS_DISABLED ||
                                                $product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE;

                                        // Check if deleted or if product is no longer visible
                                        if ($deleted == true) {
                                            $deleteProducts[] = $deltaProduct->getProductId();
                                        } else {
                                            // Get data from product exporter
                                            try {
                                                $data = $productExportModel->processProduct(
                                                    $product,
                                                    count($feedProducts)+1
                                                );
                                                if ($data != null) {
                                                    $feedProducts[] = $data;
                                                }
                                            } catch (\Exception $e) {
                                                $this->logger->error(
                                                    'ERROR: Reindex Issue from PC - Can\'t'
                                                    . ' create product model for export: '
                                                    . var_export($productHash, true)
                                                );
                                            }
                                        }
                                    }
                                }

                                if (count($feedProducts) > 0 || count($deleteProducts) > 0) {
                                    $requestBase = [
                                        'AppKey'            => $this->coreConfig->getAccessKey($store->getId()),
                                        'Secret'            => $this->coreConfig->getSecretKey($store->getId()),
                                        'Products'          => [],
                                        'DeleteProducts'    => [],
                                        'Format'            => 'magentoplugin1.0.0'
                                    ];

                                    $url = $this->serviceUrl->getDeltaEndpoint(
                                        $this->coreConfig->getRegion($store->getId())
                                    );
                                    $useSSL = $this->coreHelper->useSSL($store->getId());

                                    if ($deleteProducts) {
                                        $deleteRequest = $requestBase;
                                        $deleteRequest['DeleteProducts'] = $deleteProducts;
                                        $requests[] = $deleteRequest;
                                        $body = $this->coreHelper->formatFeed($deleteRequest, 'json');
                                        $this->coreSoapHelper->request($url, $useSSL, $body);
                                    }

                                    if ($feedProducts) {
                                        $chunks = array_chunk($feedProducts, 10);
                                        foreach ($chunks as $products) {
                                            $productRequest = $requestBase;
                                            $productRequest['Products'] = $products;
                                            $body = $this->coreHelper->formatFeed($productRequest, 'json');
                                            $this->coreSoapHelper->request($url, $useSSL, $body);
                                            $requests[] = $productRequest;
                                        }
                                    }
                                }

                                $productExportModel = null;
                            }

                            // Process any reindexes
                            if (count($reindexTasks) > 0) {
                                $this->logger->debug('PureClarity: Starting full product index...');
                                $this->selectedFeeds($store->getId(), $reindexTasks);
                                $this->logger->debug('PureClarity: Feed generation finished.');
                            }

                            $this->appEmulation->stopEnvironmentEmulation();
                        }
                    }
                }
            }
        }

        foreach ($collection as $deltaProduct) {
            $deltaProduct->delete();
        }

        return $requests;
    }

    /**
     * Saves the running_feeds state data for remaining feeds to be run (so dashboard shows correct feed status)
     * @param string[] $feeds
     * @param integer $storeId
     * @return void
     */
    private function logFeedQueue($feeds, $storeId)
    {
        $state = $this->stateRepository->getByNameAndStore('running_feeds', $storeId);
        $state->setName('running_feeds');
        $state->setValue($this->serializer->serialize($feeds));
        $state->setStoreId($storeId);

        try {
            $this->stateRepository->save($state);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('Could not save queued feeds: ' . $e->getMessage());
        }
    }

    /**
     * Removes the running_feeds state data (so dashboard shows correct feed status)
     * @param integer $storeId
     * @return void
     */
    private function removeFeedQueue($storeId)
    {
        $state = $this->stateRepository->getByNameAndStore('running_feeds', $storeId);

        try {
            $this->stateRepository->delete($state);
        } catch (CouldNotDeleteException $e) {
            $this->logger->error('Could not save queued feeds: ' . $e->getMessage());
        }
    }

    /**
     * Sorts out the state for the banner display on the dashboard.
     * @param integer $storeId
     */
    private function setBannerStatus($storeId)
    {
        try {

            $showBanner = $this->stateRepository->getByNameAndStore('show_welcome_banner', $storeId);

            if ($showBanner->getId()) {
                // set one day timer on getting started banner
                $gettingStarted = $this->stateRepository->getByNameAndStore(
                    'show_getting_started_banner',
                    $storeId
                );
                $gettingStarted->setName('show_getting_started_banner');
                $gettingStarted->setValue(time() + 86400);
                $gettingStarted->setStoreId($storeId);
                $this->stateRepository->save($gettingStarted);
                // Delete banner flags, no longer needed

                $this->stateRepository->delete($showBanner);
            }
        } catch (CouldNotSaveException $e) {
            $this->logger->error('Could not save banner status: ' . $e->getMessage());
        } catch (CouldNotDeleteException $e) {
            $this->logger->error('Could not delete banner flags: ' . $e->getMessage());
        }
    }
}
