<?php
namespace Pureclarity\Core\Model;

class Feed extends \Magento\Framework\Model\AbstractModel
{

    protected $catalogResourceModelCategoryCollectionFactory;
    protected $categoryRepository;
    protected $coreHelper;
    protected $eavConfig;
    protected $storeStoreFactory;
    protected $categoryHelper;
    protected $coreProductExportFactory;
    protected $logger;
    protected $customerGroup;
    protected $customerFactory;
    protected $orderFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catalogResourceModelCategoryCollectionFactory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Store\Model\StoreFactory $storeStoreFactory,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Pureclarity\Core\Model\ProductExportFactory $coreProductExportFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->catalogResourceModelCategoryCollectionFactory = $catalogResourceModelCategoryCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->coreHelper = $coreHelper;
        $this->eavConfig = $eavConfig;
        $this->storeStoreFactory = $storeStoreFactory;
        $this->categoryHelper = $categoryHelper;
        $this->coreProductExportFactory = $coreProductExportFactory;
        $this->logger = $logger;
        $this->customerFactory = $customerFactory;
        $this->customerGroup = $customerGroup;
        $this->orderFactory = $orderFactory;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }


    // Process the product feed and update the progress file, in page sizes of 20 (or other if overriden)
    function ProductFeed($storeId, $progressFileName, $feedFile, $doNdjson, $pageSize = 20){
        
        $productExportModel = $this->coreProductExportFactory->create();
        $productExportModel->init($storeId);

        $currentPage = 0;
        $pages = 0;
        $feedProducts = array();
        $this->coreHelper->setProgressFile($progressFileName, 'product', 0, 1);

        $isFirst = true;
        $count = 1;
        do {
            $result = $productExportModel->getFullProductFeed($pageSize, $currentPage);
            $pages = $result["Pages"];
        
            $json = "";
            foreach ($result["Products"] as $product) {
                if ($isFirst == false && !$doNdjson)
                    $json .= ',';
                $isFirst=false;
                $json .= $this->coreHelper->formatFeed($product, 'json') . ($doNdjson?PHP_EOL:'');
            }
            fwrite($feedFile, $json);

            $this->coreHelper->setProgressFile($progressFileName, 'product', $currentPage, $pages);
            $currentPage++;
        } while ($currentPage <= $pages);
        
        
        $this->coreHelper->setProgressFile($progressFileName, 'product', $currentPage, $pages);
    }



    // Process the Order History feed
    function OrderFeed($storeId, $progressFileName, $orderFilePath){
        
        // Open the file
        $orderFile = @fopen($orderFilePath, "w+");

        // Write the header
        fwrite($orderFile, "OrderId,UserId,Email,DateTimeStamp,ProdCode,Quantity,UnityPrice,LinePrice".PHP_EOL);
        
        if ((!$orderFile) || !flock($orderFile, LOCK_EX | LOCK_NB))
            throw new \Exception("Pureclarity: Cannot open orders feed file for writing (try deleting): " . $file);
        
        // Get the collection
        $fromDate = date('Y-m-d H:i:s', strtotime("-6 month"));
        $toDate = date('Y-m-d H:i:s', strtotime("now"));
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $orderCollection = $objectManager->get('Magento\Sales\Model\Order')
            ->getCollection()
            ->addAttributeToFilter('store_id', $storeId)
            ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate));
            // ->addAttributeToFilter('status', array('eq' => \Magento\Sales\Model\Order::STATE_COMPLETE));
            

        // Set size and initiate vars
        $maxProgress = count($orderCollection);
        $currentProgress = 0; 
        $counter = 0;
        $data = "";
        
        // Reset Progress file
        $this->coreHelper->setProgressFile($progressFileName, 'orders', 0, 1);
        
        // Build Data
        foreach($orderCollection as $orderData){

            $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderData->getIncrementId());
            if ($order){
                $id = $order->getIncrementId();
                $customerId = $order->getCustomerId();
                $email = $order->getCstomerEmail();
                $date = $order->getCreatedAt();
                
                $orderItems = $orderData->getAllVisibleItems();
                foreach($orderItems as $item){
                    $productId = $item->getProductId();
                    $quantity = $item->getQtyOrdered();
                    $price = $item->getPriceInclTax();
                    $linePrice = $item->getRowTotalInclTax();
                    if ($price > 0 && $linePrice>0)
                        $data .= "$id,$customerId,$email,$date,$productId,$quantity,$price,$linePrice" . PHP_EOL;
                }
                $counter += 1;
            }

            // Incremement counters
            $currentProgress += 1;

            if ($counter >= 10){
                // Every 10, write to the file.
                fwrite($orderFile, $data);
                $data = "";
                $counter = 0;
                $this->coreHelper->setProgressFile($progressFileName, 'orders', $currentProgress, $maxProgress);
            }
        }

        // Final write
        fwrite($orderFile, $data);
        fclose($orderFile);
        $this->coreHelper->setProgressFile($progressFileName, 'orders', 1, 1);        
    }



    function CategoryFeed($progressFileName, $storeId, $doNdjson) {

        $feedCategories = "";
        $currentStore = $this->storeStoreFactory->create()->load($storeId);
        $categoryCollection = $this->catalogResourceModelCategoryCollectionFactory->create()
            ->setStore($currentStore)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('pureclarity_hide_from_feed')
            ->addUrlRewriteToResult();        

        $maxProgress = count($categoryCollection);
        $currentProgress = 0;   
        $isFirst = true;
        foreach ($categoryCollection as $category) {
            
            // Get image
            $firstImage = $category->getImageUrl();
            if($firstImage != "") {
                $imageURL = $firstImage;
            } else {
                $imageURL = $this->coreHelper->getCategoryPlaceholderUrl($storeId);
            }
            $imageURL = str_replace(array("https:", "http:"), "", $imageURL);
            
            
            // Get Second Image
            $imageURL2 = null;
            $secondImage = $category->getData('pureclarity_category_image');
            if ($secondImage != "") {
                $imageURL2 = sprintf("%scatalog/category/%s", $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), $secondImage);
            } else {
                $imageURL2 = $this->coreHelper->getSecondaryCategoryPlaceholderUrl($storeId);
            }
            $imageURL2 = str_replace(array("https:", "http:"), "", $imageURL2);
            
            
            // Build Data
            $categoryData = array(
                "Id" => $category->getId(),
                "DisplayName" => $category->getName(),
                "Image" => $imageURL,
                "Link" => "/"
            );

            // Set URL and Parent ID
            if ($category->getLevel() > 1){
                $categoryUrl = str_replace($currentStore->getBaseUrl(), '', $category->getUrl($category));
                if (substr($categoryUrl, 0, 1) != '/') {
                    $categoryUrl = '/' . $categoryUrl;
                }
                $categoryData["Link"] = $categoryUrl;
                $categoryData["ParentIds"] = array($category->getParentCategory()->getId());
            }
            
            
            // Check if to ignore this category in recommenders
            if ($category->getData('pureclarity_hide_from_feed') == '1'){
                 $categoryData["ExcludeFromRecommenders"] = true;
            }

            //Check if category is active
            if (!$category->getIsActive()){
                 $categoryData["IsActive"] = false;
            }

            if ($imageURL2 != null){
                $categoryData["PCImage"] = $imageURL2;
            }
            
            if (!$isFirst && !$doNdjson)
                $feedCategories .= ',';
            $isFirst = false;

            $feedCategories .= $this->coreHelper->formatFeed($categoryData, 'json') . ($doNdjson?PHP_EOL:'');
            
            $currentProgress += 1;
            $this->coreHelper->setProgressFile($progressFileName, 'category', $currentProgress, $maxProgress);
        }
        return $feedCategories;
    }



    function BrandFeed($progressFileName, $storeId, $doNdjson){
        
        $feedBrands = [];
        $brandCategoryId = $this->coreHelper->getBrandParentCategory($storeId);
        
        if ($brandCategoryId && $brandCategoryId != "-1"){
            $category = $this->categoryRepository->get($brandCategoryId);
            
            $subcategories = $this->catalogResourceModelCategoryCollectionFactory->create()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('image')
                ->addIdFilter($category->getChildren());

            $maxProgress = count($subcategories);
            $feedBrands = "";
            $currentProgress = 0;
            $isFirst = true;
            foreach($subcategories as $subcategory) {
                $thisBrand = array(
                    "Id" => $subcategory->getId(),
                    "DisplayName" =>  $subcategory->getName()
                );
                
                $imageURL = $subcategory->getImageUrl();
                if ($imageURL){
                    $imageURL = str_replace(array("https:", "http:"), "", $imageURL);
                    $thisBrand['Image'] = $imageURL;
                }

                if (!$isFirst && !$doNdjson)
                    $feedBrands .= ',';
                $isFirst = false;
                $feedBrands .= $this->coreHelper->formatFeed($thisBrand, 'json') . ($doNdjson?PHP_EOL:'');
                $currentProgress += 1;
                $this->coreHelper->setProgressFile($progressFileName, 'brand', $currentProgress, $maxProgress);
            }
            return $feedBrands;
        }

        $this->coreHelper->setProgressFile($progressFileName, 'brand', 1, 1);
        return "";
        
    }

    function BrandFeedArray($storeId){

        $feedBrands = array();
        $brandCategoryId = $this->coreHelper->getBrandParentCategory($storeId);        
        
        if ($brandCategoryId && $brandCategoryId != "-1"){
            
            $category = $this->categoryRepository->get($brandCategoryId);
            $subcategories = $category->getChildrenCategories();
            $maxProgress = count($subcategories);
            $currentProgress = 0;
            $isFirst = true;
            foreach($subcategories as $subcategory) {
                $feedBrands[$subcategory->getId()] = $subcategory->getName();
            }
            return $feedBrands;

        }
        return [];
    }




    function UserFeed($progressFileName, $storeId, $doNdjson){

        $customerGroups = $this->customerGroup->toOptionArray();
        
        $users = "";
        $currentStore = $this->storeStoreFactory->create()->load($storeId);
        $customerCollection = $this->customerFactory->create()->getCollection()
            ->addAttributeToFilter("website_id", array("eq" => $currentStore->getWebsiteId()))
            ->addAttributeToSelect("*")
            ->load();

        $maxProgress = count($customerCollection);
        $currentProgress = 0;   
        $isFirst = true;
        foreach ($customerCollection as $customer) {

            $data = [
                'UserId' => $customer->getId(),
                'Email' => $customer->getEmail(),
                'FirstName' => $customer->getFirstname(),
                'LastName' => $customer->getLastname()
            ];
            if ($customer->getPrefix()){
                $data['Salutation'] = $customer->getPrefix();
            }
            if ($customer->getDob()){
                $data['DOB'] = $customer->getDob();
            }
            if ($customer->getGroupId() && $customerGroups[$customer->getGroupId()]){
                $data['Group'] = $customerGroups[$customer->getGroupId()]['label'];
                $data['GroupId'] = $customer->getGroupId();
            }
            if ($customer->getGender()){
                switch($customer->getGender()){
                    case 1: // Male
                        $data['Gender'] = 'M';
                    break;
                    case 2: // Female
                        $data['Gender'] = 'F';
                    break;
                }
            }

            $address = null;
            if ($customer->getDefaultShipping()){
                $address = $customer->getAddresses()[$customer->getDefaultShipping()];
            }
            else if ($customer->getAddresses() && sizeof(array_keys($customer->getAddresses())) > 0) {
                $address = $customer->getAddresses()[array_keys($customer->getAddresses())[0]];
            }
            if ($address){
                if ($address->getCity())
                    $data['City'] = $address->getCity();
                if ($address->getRegion())
                    $data['State'] = $address->getRegion();
                if ($address->getCountry())
                    $data['Country'] = $address->getCountry();
            }

            if (!$isFirst && !$doNdjson)
                $users .= ',';
            $isFirst = false;

            $users .= $this->coreHelper->formatFeed($data, 'json') . ($doNdjson?PHP_EOL:'');
            
            $currentProgress += 1;
            $this->coreHelper->setProgressFile($progressFileName, 'user', $currentProgress, $maxProgress);
        }
        return $users;
    }
}
