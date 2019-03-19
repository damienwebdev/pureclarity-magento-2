<?php
namespace Pureclarity\Core\Model;

use Pureclarity\Core\Model\Feed;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Controls the execution of feeds sent to PureClarity.
 */

class Cron extends \Magento\Framework\Model\AbstractModel
{

    protected $coreSoapHelper;
    protected $coreSftpHelper;
    protected $storeManager;
    protected $coreHelper;
    protected $coreResourceProductFeedCollectionFactory;
    protected $coreProductExportFactory;
    protected $catalogProductFactory;
    protected $logger;
    protected $coreFeedFactory;
    protected $storeStoreFactory;
    protected $scopeConfig;
    
    /** @var \Magento\Framework\Filesystem */
    private $fileSystem;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Pureclarity\Core\Helper\Soap $coreSoapHelper,
        \Pureclarity\Core\Helper\Sftp $coreSftpHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Pureclarity\Core\Model\ResourceModel\ProductFeed\CollectionFactory $coreResourceProductFeedCollectionFactory,
        \Pureclarity\Core\Model\ProductExportFactory $coreProductExportFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Pureclarity\Core\Model\FeedFactory $coreFeedFactory,
        \Magento\Store\Model\StoreFactory $storeStoreFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->coreSoapHelper = $coreSoapHelper;
        $this->coreSftpHelper = $coreSftpHelper;
        $this->storeManager = $storeManager;
        $this->coreHelper = $coreHelper;
        $this->coreResourceProductFeedCollectionFactory = $coreResourceProductFeedCollectionFactory;
        $this->coreProductExportFactory = $coreProductExportFactory;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->logger = $context->getLogger();
        $this->coreFeedFactory = $coreFeedFactory;
        $this->storeStoreFactory = $storeStoreFactory;
        $this->scopeConfig = $scopeConfig;
        $this->fileSystem = $fileSystem;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Runs all feeds, called via cron 3am daily (see /etc/crontab.xml)
     */
    public function runAllFeeds(\Magento\Cron\Model\Schedule $schedule)
    {
        $this->logger->debug('PureClarity: In Cron->runAllFeeds()');
        // Loop round each store and create feed
        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    // Only generate feeds when feed notification is active
                    if (!$this->coreHelper->isFeedNotificationActive($store->getId())) {
                        $this->allFeeds($store->getId());
                    }
                }
            }
        }
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

    /**
     * Sets selected feeds to be run by cron asap
     *
     * @param integer $storeId
     * @param string[] $feeds
     */
    public function scheduleSelectedFeeds($storeId, $feeds)
    {
        $scheduleFilePath = $this->coreHelper->getPureClarityBaseDir() . DIRECTORY_SEPARATOR .  'scheduled_feed';
        
        $schedule = [
            'store' => $storeId,
            'feeds' => $feeds
        ];
        
        $fileReader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $fileWriter = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        
        if ($fileReader->isExist($this->coreHelper->getProgressFileName())) {
            $fileWriter->delete($this->coreHelper->getProgressFileName());
        }
        
        $fileWriter->writeFile($scheduleFilePath, json_encode($schedule), 'w');
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
        //can take a while to run the feed
        set_time_limit(0);

        $hasOrder = in_array(Feed::FEED_TYPE_ORDER, $feedTypes);
        $isOrderOnly = ($hasOrder && count($feedTypes) == 1);

        $progressFileName = $this->coreHelper->getProgressFileName();
        $feedModel = $this->coreFeedFactory
            ->create()
            ->initialise($storeId, $progressFileName);
        if (! $feedModel) {
            return false;
        }

        // Post the feed data for the specified feed type
        foreach ($feedTypes as $key => $feedType) {
            switch ($feedType) {
                case Feed::FEED_TYPE_PRODUCT:
                    $feedModel->sendProducts();
                    break;
                case Feed::FEED_TYPE_CATEGORY:
                    $feedModel->sendCategories();
                    break;
                case Feed::FEED_TYPE_BRAND:
                    if ($this->coreHelper->isBrandFeedEnabled($storeId)) {
                        $feedModel->sendBrands();
                    }
                    break;
                case Feed::FEED_TYPE_USER:
                    $feedModel->sendUsers();
                    break;
                case Feed::FEED_TYPE_ORDER:
                    $feedModel->sendOrders();
                    break;
                default:
                    throw new \Exception("PureClarity feed type not recognised: {$feedType}");
            }
        }
        $feedModel->checkSuccess();
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
        return $this->coreHelper->getPureClarityBaseDir() . DIRECTORY_SEPARATOR . $this->coreHelper->getFileNameForFeed($feedType, $store->getCode());
    }

    /**
     * Reindexes products, called via cron every minute (see /etc/crontab.xml)
     */
    public function reindexData($schedule)
    {
        $this->logger->debug('PureClarity: Reindexing');
        // create a unique token until we get a response from PureClarity
        $uniqueId = 'PureClarity' . uniqid();
        $requests = [];

        $collection = $this->coreResourceProductFeedCollectionFactory->create()
                         ->addFieldToFilter('status_id', ['eq' => 0]);
 
        // Loop round each store and process Deltas
        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                foreach ($group->getStores() as $store) {
                    // Check we're allowed to do it for this store
                    if ($this->coreHelper->isProductIndexingEnabled($store->getId())) {
                        $this->logger->debug('PureClarity: Checking Reindex for StoreID: ' . $store->getId());
                    
                        $deleteProducts = $feedProducts = [];
                        
                        // Check we have something
                        if ($collection->count() > 0) {
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
                                $productExportModel = $this->coreProductExportFactory->create();
                                
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
                                        $deleted = $product->getData('status') == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED ||
                                                $product->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE;

                                        // Check if deleted or if product is no longer visible
                                        if ($deleted == true) {
                                            $deleteProducts[] = $product->getId();
                                        } else {
                                            // Get data from product exporter
                                            try {
                                                $data = $productExportModel->processProduct($product, count($feedProducts)+1);
                                                if ($data != null) {
                                                    $feedProducts[] = $data;
                                                }
                                            } catch (\Exception $e) {
                                                $this->logger->error('ERROR: Reindex Issue from PC - Can\'t create product model for export: '.var_export($productHash, true));
                                            }
                                        }
                                    }
                                }

                                if (count($feedProducts) > 0 || count($deleteProducts) > 0) {
                                    $request = [
                                        'AppKey'            => $this->coreHelper->getAccessKey($store->getId()),
                                        'Secret'            => $this->coreHelper->getSecretKey($store->getId()),
                                        'Products'          => $feedProducts,
                                        'DeleteProducts'    => $deleteProducts,
                                        'Format'            => 'magentoplugin1.0.0'
                                    ];
                                    $requests[] = $request;
                                    $body = $this->coreHelper->formatFeed($request, 'json');

                                    $url = $this->coreHelper->getDeltaEndpoint($store->getId());
                                    $useSSL = $this->coreHelper->useSSL($store->getId());

                                    $this->coreSoapHelper->request($url, $useSSL, $body);
                                }

                                $productExportModel = null;
                            }

                            // Process any reindexes
                            if (count($reindexTasks) > 0) {
                                $this->logger->debug('PureClarity: Starting full product index...');
                                $this->selectedFeeds($store->getId(), $reindexTasks);
                                $this->logger->debug('PureClarity: Feed generation finished.');
                            }
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
}
