<?php
namespace Pureclarity\Core\Model;

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
        \Psr\Log\LoggerInterface $logger,
        \Pureclarity\Core\Model\FeedFactory $coreFeedFactory,
        \Magento\Store\Model\StoreFactory $storeStoreFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
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
        $this->logger = $logger;
        $this->coreFeedFactory = $coreFeedFactory;
        $this->storeStoreFactory = $storeStoreFactory;
        $this->scopeConfig = $scopeConfig;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    

    // Cron execution
    public function runAllFeeds(\Magento\Cron\Model\Schedule $schedule)
    {
        // Loop round each store and create feed
        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    // Only generate feeds when feed notification is active
                    if (!$this->coreHelper->isFeedNotificationActive($store->getId())) {
                        allFeeds($store->getId());
                    }
                }
            }
        }
    }

    // Product All feeds in one file.
    public function allFeeds($storeId)
    {
        $this->doFeed(['product', 'category', 'brand', 'user'], $storeId, $this->getFeedFilePath('all', $storeId));
    }

    public function selectedFeeds($storeId, $feeds)
    {
        $this->doFeed($feeds, $storeId, $this->getFeedFilePath('all', $storeId));
    }

    // Produce a feed and notify PureClarity so that it can fetch it.
    public function doFeed($feedTypes, $storeId, $feedFilePath, $doNdjson = false)
    {
        
        //can take a while to run the feed
        set_time_limit(0);

        $hasOrder = in_array("orders", $feedTypes);
        $orderOnly = ($hasOrder && count($feedTypes) == 1);

        $progressFileName = $progressFileName = $this->coreHelper->getProgressFileName();
        $feedModel = $this->coreFeedFactory->create();

        // Do validations
        $host = $this->coreHelper->getSftpHost($storeId);
        $port = $this->coreHelper->getSftpPort($storeId);
        $appKey = $this->coreHelper->getAccessKey($storeId);
        $secretKey = $this->coreHelper->getSecretKey($storeId);
        if ($host == null || $port == null || $appKey == null || $secretKey == null) {
            $this->coreHelper->setProgressFile($progressFileName, 'N/A', 1, 1, "false", "false", "", "Access Key and Secret Key must be set.");
            return;
        }

        if (!$orderOnly) {
            $feedFile = @fopen($feedFilePath, "w+");
            if ((!$feedFile) || !flock($feedFile, LOCK_EX | LOCK_NB)) {
                throw new \Exception("Error: Cannot open feed file for writing under var/pureclarity directory. It could be locked or there maybe insufficient permissions to write to the directory. You must delete locked files or ensure PureClarity has permission to write to the var directory. File: " . $feedFilePath);
            }

            fwrite($feedFile, $doNdjson?'{"FileType":"ndjson", "Version": 2}' . PHP_EOL:'{ "Version": 2');
        }

        foreach ($feedTypes as &$feedType) {
            if (!$orderOnly && !$doNdjson && $feedType != "orders") {
                fwrite($feedFile, ',');
            }
            
            // Initialise Progress File.
            $this->coreHelper->setProgressFile($progressFileName, $feedType, 0, 1);
            
            // Get the feed data for the specified feed type
            switch ($feedType) {
                case 'product':
                    fwrite($feedFile, $doNdjson?'{"Type":"Products"}' . PHP_EOL:'"Products":[');
                    $feedModel->ProductFeed($storeId, $progressFileName, $feedFile, $doNdjson);
                    break;
                case 'category':
                    fwrite($feedFile, $doNdjson?'{"Type":"Categories","Version":2}' . PHP_EOL:'"Categories":[');
                    $feedData= $feedModel->CategoryFeed($progressFileName, $storeId, $doNdjson);
                    fwrite($feedFile, $feedData);
                    break;
                case 'brand':
                    fwrite($feedFile, $doNdjson?'{"Type":"Brands","Version":2}' . PHP_EOL:'"Brands":[');
                    if ($this->coreHelper->isBrandFeedEnabled($storeId)) {
                        $feedData = $feedModel->BrandFeed($progressFileName, $storeId, $doNdjson);
                        fwrite($feedFile, $feedData);
                    }
                    break;
                case 'user':
                    fwrite($feedFile, $doNdjson?'{"Type":"Users","Version":2}' . PHP_EOL:'"Users":[');
                    $feedData= $feedModel->UserFeed($progressFileName, $storeId, $doNdjson);
                    fwrite($feedFile, $feedData);
                    break;
                case 'orders':
                    $orderFilePath = $this->getFeedFilePath('orders', $storeId);
                    $feedModel->OrderFeed($storeId, $progressFileName, $orderFilePath);
                    break;
                default:
                    throw new \Exception("Pureclarity feed type not recognised: $feedType");
            }
            
            if (!$orderOnly && !$doNdjson && $feedType != "orders") {
                fwrite($feedFile, ']');
            }
        }
        
        if (!$orderOnly) {
            if (!$doNdjson) {
                fwrite($feedFile, '}');
            }
            fclose($feedFile);
        }

        // Ensure progress file is set to complete
        $this->coreHelper->setProgressFile($progressFileName, 'N/A', 1, 1, "true", "false");

        // Uploade to sftp
        if (!$orderOnly) {
            $uniqueId = 'PureClarityFeed-' . uniqid() . ".ndjson";
            $uploadSuccess = $this->coreSftpHelper->send($host, $port, $appKey, $secretKey, $uniqueId, $feedFilePath, 'magento-feeds');
        }
        if ($hasOrder) {
            $uniqueId = 'Orders-' . uniqid() . ".csv";
            $uploadSuccess = $this->coreSftpHelper->send($host, $port, $appKey, $secretKey, $uniqueId, $orderFilePath);
        }
        if ($uploadSuccess) {
            // Set to uploaded
            $this->coreHelper->setProgressFile($progressFileName, 'N/A', 1, 1, "true", "true");
        } else {
            $this->coreHelper->setProgressFile($progressFileName, 'N/A', 1, 1, "true", "false", "There was a problem uploading the feed. Please see error logs for more information.");
        }
    }
    

    
    // Produce a product feed and notify PureClarity so that it can fetch it.
    public function fullProductFeed($storeId)
    {
        $this->doFeed(['product'], $storeId, $this->getFeedFilePath('product', $storeId));
    }
    // Produce a category feed and notify PureClarity so that it can fetch it.
    public function fullCategoryFeed($storeId)
    {
        $this->doFeed(['category'], $storeId, $this->getFeedFilePath('category', $storeId));
    }
    // Produce a brand feed and notify PureClarity so that it can fetch it.
    public function fullBrandFeed($storeId)
    {
        $this->doFeed(['brand'], $storeId, $this->getFeedFilePath('brand', $storeId));
    }

    private function getFeedFilePath($feedType, $storeId)
    {
        $store = $this->storeStoreFactory->create()->load($storeId);
        return $this->coreHelper->getPureClarityBaseDir() . DIRECTORY_SEPARATOR . $this->coreHelper->getFileNameForFeed($feedType, $store->getCode());
    }



    /**
     * Reindex Products
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

                                    $response = $this->coreSoapHelper->request($url, $useSSL, $body);
                                    $response = json_decode($response);
                                    if (!is_object($response)) {
                                        $this->logger->error('ERROR: Reindex Issue from PC - '.var_export($productHash, true));
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
