<?php
namespace Pureclarity\Core\Model;

/**
* PureClarity Product Export Module
*/
class ProductExport extends \Magento\Framework\Model\AbstractModel
{

    public $storeId = null;
    public $baseCurrencyCode = null;
    public $currenciesToProcess = array();
    public $attributesToInclude = array();
    public $seenProductIds = array();
    public $currentStore = null;
    public $brandLookup = array();
    protected $categoryCollection = [];

    
    protected $storeManager;
    protected $storeStoreFactory;
    protected $directoryCurrencyFactory;
    protected $coreHelper;
    protected $coreFeedFactory;
    protected $catalogResourceModelProductAttributeCollectionFactory;
    protected $coreResourceProductCollectionFactory;
    protected $catalogResourceModelCategoryCollectionFactory;
    protected $catalogImageHelper;
    protected $catalogInventoryStockItemFactory;
    protected $configurableProductProductTypeConfigurableFactory;
    protected $directoryHelper;
    protected $catalogConfig;
    protected $catalogProductFactory;
    protected $catalogHelper;
    protected $logger;
    protected $eavConfig;
    protected $swatchHelper;
    protected $swatchMediaHelper;
    protected $blockFactory;
    protected $galleryReadHandler;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\StoreFactory $storeStoreFactory,
        \Magento\Directory\Model\CurrencyFactory $directoryCurrencyFactory,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Pureclarity\Core\Model\FeedFactory $coreFeedFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $catalogResourceModelProductAttributeCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $coreResourceProductCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catalogResourceModelCategoryCollectionFactory,
        \Magento\Catalog\Helper\Image $catalogImageHelper,
        \Magento\CatalogInventory\Model\Stock\ItemFactory $catalogInventoryStockItemFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory $configurableProductProductTypeConfigurableFactory,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Swatches\Helper\Data $swatchHelper,
        \Magento\Swatches\Helper\Media $swatchMediaHelper,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        \Magento\Catalog\Model\Product\Gallery\ReadHandler $galleryReadHandler,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->storeStoreFactory = $storeStoreFactory;
        $this->directoryCurrencyFactory = $directoryCurrencyFactory;
        $this->coreHelper = $coreHelper;
        $this->coreFeedFactory = $coreFeedFactory;
        $this->catalogResourceModelProductAttributeCollectionFactory = $catalogResourceModelProductAttributeCollectionFactory;
        $this->coreResourceProductCollectionFactory = $coreResourceProductCollectionFactory;
        $this->catalogResourceModelCategoryCollectionFactory = $catalogResourceModelCategoryCollectionFactory;
        $this->catalogImageHelper = $catalogImageHelper;
        $this->catalogInventoryStockItemFactory = $catalogInventoryStockItemFactory;
        $this->configurableProductProductTypeConfigurableFactory = $configurableProductProductTypeConfigurableFactory;
        $this->directoryHelper = $directoryHelper;
        $this->catalogConfig = $catalogConfig;
        $this->catalogHelper = $catalogHelper;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->logger = $logger;
        $this->eavConfig = $eavConfig;
        $this->swatchHelper = $swatchHelper;
        $this->swatchMediaHelper = $swatchMediaHelper;
        $this->blockFactory = $blockFactory;
        $this->galleryReadHandler = $galleryReadHandler;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }
    
    // Initialise the model ready to call the product data for the give store.
    public function init($storeId)
    {
        // Use this store, if not passed in.
        $this->storeId = $storeId;
        if (is_null($this->storeId)) {
            $this->storeId = $this->storeManager->getStore()->getId();
        }
        
        $this->currentStore = $this->storeStoreFactory->create()->load($this->storeId);

        // Set Currency list
        $currencyModel = $this->directoryCurrencyFactory->create(); 
        $this->baseCurrencyCode = $this->currentStore->getBaseCurrencyCode();
        $currencies = $this->currentStore->getAllowedCurrencies();
        $currencyRates = $currencyModel->getCurrencyRates($this->baseCurrencyCode, array_values($currencies));
        $this->currenciesToProcess[] = $this->baseCurrencyCode;
        if ($currencyRates != null){
            foreach($currencies as $currency){
                if ($currency != $this->baseCurrencyCode && !empty($currencyRates[$currency])){
                    $this->currenciesToProcess[] = $currency;
                }
            }
        }

        // Manage Brand
        $this->brandLookup = [];
        // If brand feed is enabled, get the brands
        if($this->coreHelper->isBrandFeedEnabled($this->storeId)) {
            $feedModel = $this->coreFeedFactory->create();
            $this->brandLookup = $feedModel->BrandFeedArray($this->storeId);
        }

        // Get Attributes
        $attributes = $this->catalogResourceModelProductAttributeCollectionFactory->create()->getItems();
        $attributesToExclude = array("prices", "price", "category_ids", "sku");

        // Get list of attributes to include
        foreach ($attributes as $attribute){
            $code = $attribute->getAttributecode();
            
            if (!in_array(strtolower($code), $attributesToExclude) && !empty($attribute->getFrontendLabel())) {
                $this->attributesToInclude[] = array($code, $attribute->getFrontendLabel());
            }
        }

        // Get Category List 
        $this->categoryCollection = [];
        $categoryCollection = $this->catalogResourceModelCategoryCollectionFactory->create()
                ->addAttributeToSelect('name')
                ->addFieldToFilter('is_active', array("in" => array('1')));
        foreach($categoryCollection as $category){
            $this->categoryCollection[$category->getId()] = $category->getName();
        }
    }


    
    // Get the full product feed for the given page and size
    public function getFullProductFeed($pageSize = 1000000, $currentPage = 1)
    {
        // Get product collection
        $validVisiblity = array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH, 
                                              \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG, 
                                              \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH));
        
        $products = $this->coreResourceProductCollectionFactory->create()
            ->setStoreId($this->storeId)
            ->addUrlRewrite()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter("status", array("eq" => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED))
            ->addFieldToFilter('visibility', $validVisiblity)
            ->setPageSize($pageSize)
            ->setCurPage($currentPage);
            
        // Get pages
        $pages = $products->getLastPageNumber();
        if ($currentPage > $pages) {
            $products = array();
        }
        
        // Loop through products
        $feedProducts = array();
        foreach($products as $product) {
            $data = $this->processProduct($product, count($feedProducts)+($pageSize * $currentPage)+1);
            if ($data != null)
                $feedProducts[] = $data;
        }
        
        return  array(
            "Pages" => $pages,
            "Products" => $feedProducts
        );
    }

    // Gets the data for a product.
    public function processProduct(&$product, $index)
    {
        // Check hash that we've not already seen this product
        if(!array_key_exists($product->getId(), $this->seenProductIds) || $this->seenProductIds[$product->getId()]===null) {

            // Set Category Ids for product
            $categoryIds = $product->getCategoryIds();

            // Get a list of the category names
            $categoryList = [];
            $brandId = null;
            foreach ($categoryIds as $id) {
                if (array_key_exists($id, $this->categoryCollection)){
                    $categoryList[] = $this->categoryCollection[$id];
                }
                if (!$brandId && array_key_exists($id, $this->brandLookup)){
                    $brandId = $id;
                }
            }
            
            // Get Product Link URL
            $urlParams = [
                '_nosid' => true,
            ];
            $productUrl = $product->getUrlModel()->getUrl($product, $urlParams);
            if ($productUrl){
                $productUrl = str_replace(array("https:", "http:"), "", $productUrl);
            }

            // Get Product Image URL
            $productImageUrl = '';
            if($product->getImage() && $product->getImage() != 'no_selection')
                $productImageUrl = $this->currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)."catalog/product/".$product->getImage();
            else 
                $productImageUrl = $this->currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)."catalog/product/placeholder/". $this->currentStore->getConfig("catalog/placeholder/image_placeholder");
            $productImageUrl = str_replace(array("https:", "http:"), "", $productImageUrl);

        
            $this->galleryReadHandler->execute($product);
            $productImages = $product->getMediaGalleryImages();
            $allImages = [];
            foreach($productImages as $image){
                $this->logger->debug(json_encode($image->getUrl()));
                $allImages[] = str_replace(array("https:", "http:"), "", $image->getUrl());
            }

            // Set standard data
            $data = array(
                "_index" => $index,
                "Id" => $product->getId(),
                "Sku" => $product->getSku(),
                "Title" => $product->getName(),
                "Description" => array(strip_tags($product->getData('description')), strip_tags($product->getShortDescription())),
                "Link" => $productUrl,
                "Image" => $productImageUrl,
                "Categories" => $categoryIds,
                "MagentoCategories" => array_values(array_unique($categoryList, SORT_STRING)),
                "MagentoProductType" => $product->getTypeId(),
                "InStock" => $product->getExtensionAttributes ($this->catalogInventoryStockItemFactory->create()->setProduct($product)->getIsInStock() == 1) ? true : false
            );

            if (sizeof($allImages) > 0){
                $data["AllImages"] = $allImages;
            }

            // Swatch renderer
            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
                session_write_close();
                $swatchBlock = $this->blockFactory
                                    ->createBlock('\Magento\Swatches\Block\Product\Renderer\Listing\Configurable')
                                    ->setData("product", $product);
                $jsonConfig = $swatchBlock->getJsonConfig();
                $data["jsonconfig"] = $jsonConfig;
                $data["swatchrenderjson"] = json_encode([
                    "selectorProduct" => '.product-item-details',
                    "onlySwatches" => true,
                    "enableControlLabel" => false,
                    "numberToShow" => $swatchBlock->getNumberSwatchesPerProduct(),
                    "jsonConfig" => json_decode($jsonConfig),
                    "jsonSwatchConfig" => json_decode($swatchBlock->getJsonSwatchConfig()),
                    "mediaCallback" => $this->currentStore->getBaseUrl() . "swatches/ajax/media/"
                ]);
                $swatchBlock = null;
            }
            
            
            // Set the visibility for PureClarity
            $visibility = $product->getVisibility();
            if ($visibility == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG){
                $data["ExcludeFromSearch"] = true;
            }
            else if ($visibility == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH){
                $data["ExcludeFromProductListing"] = true;
            }

            // Set Brand
            if ($brandId){
                $data["Brand"] = $brandId;
            }

            // Set PureClarity Custom values
            $searchTag = $product->getData('pureclarity_search_tags');
            if ($searchTag != null && $searchTag != '')
                 $data["SearchTags"] = array($searchTag);

            $overlayImage = $product->getData('pureclarity_overlay_image');
            if ($overlayImage != ""){
                $overlayImage = str_replace(array("https:", "http:"), "", $overlayImage);
                $data["ImageOverlay"] = $this->coreHelper->getPlaceholderUrl($this->currentStore) . $overlayImage;
            }

            if ($product->getData('pureclarity_exc_rec') == '1')
                 $data["ExcludeFromRecommenders"] = true;
        
            if ($product->getData('pureclarity_newarrival') == '1')
                 $data["NewArrival"] = true;
            
            if ($product->getData('pureclarity_onoffer') == '1')
                 $data["OnOffer"] = true;

            // Add attributes
            $this->setAttributes($product, $data);
            

            // Look for child products in Configurable, Grouped or Bundled products
            $childProducts = array();
            switch ($product->getTypeId()) {
                case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                    $childIds = $this->configurableProductProductTypeConfigurableFactory->create()->getChildrenIds($product->getId());
                    if (count($childIds[0]) > 0){
                        $childProducts = $this->coreResourceProductCollectionFactory->create()->addAttributeToSelect('*')
                            ->addFieldToFilter('entity_id', array('in'=> $childIds[0]));
                    }else{
                        //configurable with no children - exlude from feed
                        return null;
                    }
                    break;
                case \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE:
                    $childProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);
                    break;
                case \Magento\Bundle\Model\Product\Type::TYPE_CODE:
                    $childProducts = $product->getTypeInstance(true)->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);
                    break;
            }

            // Process any child products
            $this->childProducts($childProducts, $data);

            // Set prices
            $this->setProductPrices($product, $data, $childProducts);

            // Add to hash to make sure we don't get dupes
            $this->seenProductIds[$product->getId()] = true;

            // Add to feed array
            return $data;
        }

        return null;
    }

    protected function childProducts($products, &$data){
        foreach($products as $product){
            $this->setProductData($product, $data);
            $this->setAttributes($product, $data);
        }
    }

    protected function setProductData($product, &$data)
    {
        $this->addValueToDataArray($data, 'AssociatedSkus', $product->getData('sku'));
        $this->addValueToDataArray($data, 'AssociatedTitles', $product->getData('name'));
        $this->addValueToDataArray($data, 'Description', strip_tags($product->getData('description')));
        $this->addValueToDataArray($data, 'Description', strip_tags($product->getShortDescription()));
        $searchTag = $product->getData('pureclarity_search_tags');
        if ($searchTag != null && $searchTag != '')
            $this->addValueToDataArray($data, 'SearchTags', $searchTag);
    }

    protected function addValueToDataArray(&$data, $key, $value){

        if (!array_key_exists($key,$data)){
            $data[$key][] = $value;
        }else if ($value !== null && (!is_array($data[$key]) || !in_array($value, $data[$key]))){
            $data[$key][] = $value;
        }
    }

    protected function setProductPrices($product, &$data, &$childProducts = null)
    {
        $basePrices = $this->getProductPrice($product, false, true, $childProducts);
        $baseFinalPrices = $this->getProductPrice($product, true, true, $childProducts);
        foreach($this->currenciesToProcess as $currency){
            // Process currency for min price
            $minPrice = $this->convertCurrency($basePrices['min'], $currency);
            $this->addValueToDataArray($data, 'Prices', number_format($minPrice, 2, '.', '').' '.$currency);
            $minFinalPrice = $this->convertCurrency($baseFinalPrices['min'], $currency);
            if ($minFinalPrice !== null && $minFinalPrice < $minPrice){
                $this->addValueToDataArray($data, 'SalePrices', number_format($minFinalPrice, 2,'.', '').' '.$currency);
            }
            // Process currency for max price if it's different to min price
            $maxPrice = $this->convertCurrency($basePrices['max'], $currency);
            if ($minPrice<$maxPrice){
                $this->addValueToDataArray($data, 'Prices', number_format($maxPrice, 2, '.', '').' '.$currency);
                $maxFinalPrice = $this->convertCurrency($baseFinalPrices['max'], $currency);
                if ($maxFinalPrice !== null && $maxFinalPrice < $maxPrice){
                    $this->addValueToDataArray($data, 'SalePrices', number_format($maxFinalPrice, 2,'.', '').' '.$currency);
                }
            }
        }
    }

     protected function convertCurrency($price, $to){
        if ($to === $this->baseCurrencyCode) return $price;
        return $this->directoryHelper->currencyConvert($price, $this->baseCurrencyCode, $to);
    }

     protected function getProductPrice(\Magento\Catalog\Model\Product $product, $getFinalPrice = false, $includeTax = true, &$childProducts = null) {
        $minPrice = 0;
        $maxPrice = 0;
        switch ($product->getTypeId()) {
            case \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE:
            case \Magento\Bundle\Model\Product\Type::TYPE_CODE:
                $config = $this->catalogConfig;
                $groupProduct = $this->coreResourceProductCollectionFactory->create()
                    ->setStoreId($this->storeId)
                    ->addAttributeToSelect($config->getProductAttributes())
                    ->addAttributeToFilter('entity_id', $product->getId())
                    ->setPage(1, 1)
                    ->addMinimalPrice()
                    ->addTaxPercents()
                    ->load()
                    ->getFirstItem();
                if ($groupProduct) {
                    $minPrice = $groupProduct->getMinimalPrice();
                    $maxPrice = $groupProduct->getMaxPrice();
                    if ($includeTax) {
                        $minPrice = $this->catalogHelper->getTaxPrice($groupProduct, $minPrice, true);
                        $maxPrice = $this->catalogHelper->getTaxPrice($groupProduct, $maxPrice, true);
                    }
                }
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $price = null;
                $lowestPrice = 0;
                $highestPrice = 0;
                $associatedProducts = ($childProducts !== null) ? 
                                        $childProducts : 
                                        $this->configurableProductProductTypeConfigurableFactory->create()
                                        ->getUsedProducts(null, $product);
                foreach ($associatedProducts as $associatedProduct) {
                    if (!$associatedProduct->isDisabled()){
                        $productModel = $this->catalogProductFactory->create()->load($associatedProduct->getId());
                        $variationPrices = $this->getProductPrice($productModel, $getFinalPrice, true);
                        
                        if ($lowestPrice == 0 || $variationPrices['min'] < $lowestPrice) {
                            $lowestPrice = $variationPrices['min'];
                        }
                        if ($highestPrice == 0 || $variationPrices['max'] > $highestPrice){
                            $highestPrice = $variationPrices['max'];
                        }
                    }
                }
                
                $minPrice = $lowestPrice;
                $maxPrice = $highestPrice;
                break;
            default:
                $minPrice = $this->getDefaultFromProduct($product,$getFinalPrice,$includeTax);
                $maxPrice = $minPrice;
                break;
        }
        return array('min' => $minPrice, 'max' => $maxPrice);
    }

    protected function getDefaultFromProduct(\Magento\Catalog\Model\Product $product, $getFinalPrice = false, $includeTax = true) 
    {
        $price = $getFinalPrice ? $product->getFinalPrice() : $product->getPrice();
        if ($includeTax) {
            $price = $this->catalogHelper->getTaxPrice($product, $price, true);
        }
        return $price;
    }


    protected function setAttributes(\Magento\Catalog\Model\Product $product, &$data)
    {  
        foreach ($this->attributesToInclude as $attribute) {
            $code = $attribute[0];
            $name = $attribute[1];
            if ($product->getData($code) != null) {
                try{
                    $attrValue = $product->getAttributeText($code);
                }
                catch (\Exception $e){
                    // Unable to read attribute text
                    continue;
                }
                if (!empty($attrValue)){
                    if (is_array($attrValue)){
                        foreach($attrValue as $value){
                            $this->addValueToDataArray($data, $name, $value);
                        }
                    }
                    else {
                        $this->addValueToDataArray($data, $name, $attrValue);
                    }
                }
            }
        }
    }

    protected function convertPrice($price, $round = false)
    {
        if (empty($price)) {
            return 0;
        }

        $price = $this->currentStore->convertPrice($price);
        if ($round) {
            $price = $this->currentStore->roundPrice($price);
        }

        return $price;
    }


}


