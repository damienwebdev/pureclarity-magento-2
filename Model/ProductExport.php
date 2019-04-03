<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

/**
 * PureClarity Product Export Module
 * For example, used to create product feed that's sent to PureClarity.
 */
class ProductExport extends \Magento\Framework\Model\AbstractModel
{
    /** @var string[] */
    private $selectAttributeTypes = [
        'select',
        'multiselect',
        'boolean'
    ];
    
    public $storeId = null;
    public $baseCurrencyCode = null;
    public $currenciesToProcess = [];
    public $attributesToInclude = [];
    public $seenProductIds = [];
    /** @var \Magento\Store\Model\Store */
    public $currentStore = null;
    public $brandLookup = [];
    protected $categoryCollection = [];
    
    protected $storeManager;
    protected $storeFactory;
    protected $directoryCurrencyFactory;
    protected $coreHelper;
    protected $coreFeedFactory;
    protected $catalogResourceModelProductAttributeCollectionFactory;
    protected $coreResourceProductCollectionFactory;
    protected $catalogResourceModelCategoryCollectionFactory;
    protected $catalogImageHelper;
    protected $stockRegistry;
    protected $configurableProductProductTypeConfigurableFactory;
    protected $directoryHelper;
    protected $catalogConfig;
    protected $catalogProductFactory;
    protected $logger;
    protected $eavConfig;
    protected $swatchHelper;
    protected $swatchMediaHelper;
    protected $blockFactory;
    protected $galleryReadHandler;
    
    /** @var \Pureclarity\Core\Model\ProductExport\PriceHandler */
    private $corePriceHandler;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Directory\Model\CurrencyFactory $directoryCurrencyFactory,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Pureclarity\Core\Model\FeedFactory $coreFeedFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $catalogResourceModelProductAttributeCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $coreResourceProductCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catalogResourceModelCategoryCollectionFactory,
        \Magento\Catalog\Helper\Image $catalogImageHelper,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory $configurableProductProductTypeConfigurableFactory,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Swatches\Helper\Data $swatchHelper,
        \Magento\Swatches\Helper\Media $swatchMediaHelper,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        \Pureclarity\Core\Model\ProductExport\PriceHandler $corePriceHandler,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->storeFactory = $storeFactory;
        $this->directoryCurrencyFactory = $directoryCurrencyFactory;
        $this->coreHelper = $coreHelper;
        $this->coreFeedFactory = $coreFeedFactory;
        $this->catalogResourceModelProductAttributeCollectionFactory = $catalogResourceModelProductAttributeCollectionFactory;
        $this->coreResourceProductCollectionFactory = $coreResourceProductCollectionFactory;
        $this->catalogResourceModelCategoryCollectionFactory = $catalogResourceModelCategoryCollectionFactory;
        $this->catalogImageHelper = $catalogImageHelper;
        $this->stockRegistry = $stockRegistry;
        $this->configurableProductProductTypeConfigurableFactory = $configurableProductProductTypeConfigurableFactory;
        $this->directoryHelper = $directoryHelper;
        $this->catalogConfig = $catalogConfig;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->logger = $context->getLogger();
        $this->eavConfig = $eavConfig;
        $this->swatchHelper = $swatchHelper;
        $this->swatchMediaHelper = $swatchMediaHelper;
        $this->blockFactory = $blockFactory;
        $this->corePriceHandler = $corePriceHandler;

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
        
        $this->currentStore = $this->storeFactory->create()->load($this->storeId);

        // Set Currency list
        $currencyModel = $this->directoryCurrencyFactory->create();
        $this->baseCurrencyCode = $this->currentStore->getBaseCurrencyCode();
        $currencies = $this->currentStore->getAllowedCurrencies();
        $currencyRates = $currencyModel->getCurrencyRates($this->baseCurrencyCode, array_values($currencies));
        $this->currenciesToProcess[] = $this->baseCurrencyCode;
        if ($currencyRates != null) {
            foreach ($currencies as $currency) {
                if ($currency != $this->baseCurrencyCode && !empty($currencyRates[$currency])) {
                    $this->currenciesToProcess[] = $currency;
                }
            }
        }

        // Manage Brand
        $this->brandLookup = [];
        // If brand feed is enabled, get the brands
        if ($this->coreHelper->isBrandFeedEnabled($this->storeId)) {
            $feedModel = $this->coreFeedFactory->create();
            $this->brandLookup = $feedModel->BrandFeedArray($this->storeId);
        }

        // Get Attributes
        $attributes = $this->catalogResourceModelProductAttributeCollectionFactory->create()->getItems();
        $attributesToExclude = ["prices", "price", "category_ids", "sku"];

        // Get list of attributes to include
        foreach ($attributes as $attribute) {
            /** @var $attribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
            $code = $attribute->getAttributecode();
            
            if (!in_array(strtolower($code), $attributesToExclude) && !empty($attribute->getFrontendLabel())) {
                $this->attributesToInclude[] = [
                    'code' => $code,
                    'label' => $attribute->getFrontendLabel(),
                    'type' => $attribute->getFrontendInput()
                ];
            }
        }

        // Get Category List
        $this->categoryCollection = [];
        $categoryCollection = $this->catalogResourceModelCategoryCollectionFactory->create()
                ->addAttributeToSelect('name')
                ->addFieldToFilter('is_active', ["in" => ['1']]);
        foreach ($categoryCollection as $category) {
            $this->categoryCollection[$category->getId()] = $category->getName();
        }
    }
    
    // Get the full product feed for the given page and size
    public function getFullProductFeed($pageSize = 1000000, $currentPage = 1)
    {
        // Get product collection
        $validVisiblity = [
            'in' => [
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH
            ]
        ];
        $products = $this->coreResourceProductCollectionFactory->create()
            ->setStoreId($this->storeId)
            ->addStoreFilter($this->storeId)
            ->addUrlRewrite()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter("status", ["eq" => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED])
            ->addFieldToFilter('visibility', $validVisiblity)
            ->addMinimalPrice()
            ->addTaxPercents()
            ->setPageSize($pageSize)
            ->setCurPage($currentPage);
            
        // Get pages
        $pages = $products->getLastPageNumber();
        if ($currentPage > $pages) {
            $products = [];
        }
        
        // Loop through products
        $feedProducts = [];
        foreach ($products as $product) {
            $data = $this->processProduct($product, count($feedProducts) + ($pageSize * $currentPage) + 1);
            if ($data != null) {
                $feedProducts[] = $data;
            }
        }
        
        return  [
            "Pages" => $pages,
            "Products" => $feedProducts
        ];
    }

    // Gets the data for a product.
    public function processProduct(&$product, $index)
    {
        session_write_close(); //ensures progress feed in GUI is updated

        // Check hash that we've not already seen this product
        if (!array_key_exists($product->getId(), $this->seenProductIds) || $this->seenProductIds[$product->getId()]===null) {
            // Set Category Ids for product
            $categoryIds = $product->getCategoryIds();

            // Get a list of the category names
            $categoryList = [];
            $brandId = null;
            foreach ($categoryIds as $id) {
                if (array_key_exists($id, $this->categoryCollection)) {
                    $categoryList[] = $this->categoryCollection[$id];
                }
                if (!$brandId && array_key_exists($id, $this->brandLookup)) {
                    $brandId = $id;
                }
            }
            
            // Get Product Link URL
            $urlParams = [
                '_nosid' => true,
                '_scope' => $this->storeId
            ];
            $productUrl = $product->setStoreId($this->storeId)->getUrlModel()->getUrl($product, $urlParams);
            
            if ($productUrl) {
                $productUrl = str_replace(["https:", "http:"], "", $productUrl);
            }
            
            // Get Product Image URL
            $baseProductImageUrl = $this->currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . "catalog/product/";
            $productImageUrl = $baseProductImageUrl;
            if ($product->getImage() && $product->getImage() != 'no_selection') {
                $productImageUrl .= $product->getImage();
            } else {
                $productImageUrl .= "placeholder/". $this->currentStore->getConfig("catalog/placeholder/image_placeholder");
            }
            $productImageUrl = str_replace(["https:", "http:"], "", $productImageUrl);

            /**
             * \Magento\Catalog\Model\Product\Gallery\ReadHandler does not exist in Magento 2.0
             * - this is a workaround which avoids having the ReadHandler as a constructor parameter
             */
            if ($this->getGalleryReadHandler()) {
                $this->getGalleryReadHandler()->execute($product);
                $productImages = $product->getMediaGalleryImages();
            } else {
                $productImages = [];
                $productImages[] = $baseProductImageUrl . $product->getImage();
                $productImages[] = $baseProductImageUrl . $product->getThumbnail();
                $productImages[] = $baseProductImageUrl . $product->getSmallImage();
            }

            $allImages = [];
            foreach ($productImages as $image) {
                $allImages[] = str_replace(["https:", "http:"], "", (is_object($image) ? $image->getUrl() : $image));
            }

            // Set standard data
            $data = [
                "_index" => $index,
                "Id" => $product->getId(),
                "Sku" => $product->getSku(),
                "Title" => $product->getName(),
                "Description" => [strip_tags($product->getData('description')), strip_tags($product->getShortDescription())],
                "Link" => $productUrl,
                "Image" => $productImageUrl,
                "Categories" => $categoryIds,
                "MagentoCategories" => array_values(array_unique($categoryList, SORT_STRING)),
                "MagentoProductType" => $product->getTypeId(),
                "InStock" => $this->stockRegistry->getStockItem($product->getId())->getIsInStock() ? 'true' : 'false'
            ];

            if (sizeof($allImages) > 0) {
                $data["AllImages"] = $allImages;
            }

            // Swatch renderer
            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
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
            if ($visibility == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG) {
                $data["ExcludeFromSearch"] = true;
            } elseif ($visibility == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH) {
                $data["ExcludeFromProductListing"] = true;
            }

            // Set Brand
            if ($brandId) {
                $data["Brand"] = $brandId;
            }

            // Set PureClarity Custom values
            $searchTagString = $product->getData('pureclarity_search_tags');
            if (!empty($searchTagString)) {
                $searchTags = explode(",", $searchTagString);
                if (count($searchTags)) {
                    foreach ($searchTags as $key => &$searchTag) {
                        $searchTag = trim($searchTag);
                        if (empty($searchTag)) {
                            unset($searchTags[$key]);
                        }
                    }
                    if (count($searchTags)) {
                        $data["SearchTags"] = array_values($searchTags);
                    }
                }
            }

            $overlayImage = $product->getData('pureclarity_overlay_image');
            if ($overlayImage != "") {
                $overlayImage = str_replace(["https:", "http:"], "", $overlayImage);
                $data["ImageOverlay"] = $this->coreHelper->getPlaceholderUrl($this->currentStore) . $overlayImage;
            }

            if ($product->getData('pureclarity_exc_rec') == '1') {
                 $data["ExcludeFromRecommenders"] = true;
            }
        
            if ($product->getData('pureclarity_newarrival') == '1') {
                 $data["NewArrival"] = true;
            }
            
            if ($product->getData('pureclarity_onoffer') == '1') {
                 $data["OnOffer"] = true;
            }

            // Add attributes
            $this->setAttributes($product, $data);

            // Look for child products in Configurable, Grouped or Bundled products
            $childProducts = [];
            switch ($product->getTypeId()) {
                case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                    $childIds = $this->configurableProductProductTypeConfigurableFactory->create()
                        ->getChildrenIds($product->getId());
                    if (count($childIds[0]) > 0) {
                        $childProducts = $this->coreResourceProductCollectionFactory->create()
                            ->addAttributeToSelect('*')
                            ->addFieldToFilter('entity_id', [
                                'in' => $childIds[0]
                            ]);
                            
                        $childProducts = $childProducts->getItems();
                    } else {
                        //configurable with no children - exclude from feed
                        return null;
                    }
                    break;
                case \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE:
                    $childProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);
                    break;
                case \Magento\Bundle\Model\Product\Type::TYPE_CODE:
                    $childProducts = $product->getTypeInstance(true)->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);
                    $childProducts = $childProducts->getItems();
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

    protected function childProducts($products, &$data)
    {
        foreach ($products as $product) {
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
        if ($searchTag != null && $searchTag != '') {
            $this->addValueToDataArray($data, 'SearchTags', $searchTag);
        }
    }

    protected function addValueToDataArray(&$data, $key, $value)
    {

        if (!array_key_exists($key, $data)) {
            $data[$key][] = $value;
        } elseif ($value !== null && (!is_array($data[$key]) || !in_array($value, $data[$key]))) {
            $data[$key][] = $value;
        }
    }

    protected function setProductPrices($product, &$data, &$childProducts = null)
    {
        $prices = $this->corePriceHandler->getProductPrices(
            $this->currentStore,
            $product,
            true,
            $childProducts
        );
        foreach ($this->currenciesToProcess as $currency) {
            foreach ($prices as $priceType => $priceData) {
                $priceKey = $priceType == 'base' ? 'Prices' : 'Prices_' . $priceType;
                $saleKey = $priceType == 'base' ? 'SalePrices' : 'SalePrices_' . $priceType;
                // Process currency for min price
                $minPrice = $this->convertCurrency($priceData['min'], $currency);
                $this->addValueToDataArray($data, $priceKey, number_format($minPrice, 2, '.', '') . ' ' . $currency);
                $minFinalPrice = $this->convertCurrency($priceData['min-final'], $currency);
                
                if ($minFinalPrice !== null && $minFinalPrice < $minPrice) {
                    $this->addValueToDataArray(
                        $data,
                        $saleKey,
                        number_format($minFinalPrice, 2, '.', '') . ' ' . $currency
                    );
                }
                // Process currency for max price if it's different to min price
                $maxPrice = $this->convertCurrency($priceData['max'], $currency);
                if ($minPrice < $maxPrice) {
                    $this->addValueToDataArray($data, $priceKey, number_format($maxPrice, 2, '.', '').' '.$currency);
                    $maxFinalPrice = $this->convertCurrency($priceData['max-final'], $currency);
                    if ($maxFinalPrice !== null && $maxFinalPrice < $maxPrice) {
                        $this->addValueToDataArray(
                            $data,
                            $saleKey,
                            number_format($maxFinalPrice, 2, '.', '') . ' ' . $currency
                        );
                    }
                }
            }
        }
    }

    protected function convertCurrency($price, $to)
    {
        if ($to === $this->baseCurrencyCode) {
            return $price;
        }
        return $this->directoryHelper->currencyConvert($price, $this->baseCurrencyCode, $to);
    }

    protected function setAttributes(\Magento\Catalog\Model\Product $product, &$data)
    {
        foreach ($this->attributesToInclude as $attribute) {
            $code = $attribute['code'];
            $name = $attribute['label'];
            
            if ($product->getData($code) !== null) {
                try {
                    if (in_array($attribute['type'], $this->selectAttributeTypes)) {
                        $attrValue = $product->getAttributeText($code);
                    } else {
                        $attrValue = $product->getData($code);
                    }
                } catch (\Exception $e) {
                    // Unable to read attribute text
                    continue;
                }
                if ($attrValue !== null) {
                    if (is_array($attrValue)) {
                        foreach ($attrValue as $value) {
                            $this->addValueToDataArray($data, $name, $value);
                        }
                    } else {
                        $this->addValueToDataArray($data, $name, $attrValue);
                    }
                }
            }
        }
    }

    /**
     * Returns \Magento\Catalog\Model\Product\Gallery\ReadHandler if the class exists,
     * otherwise returns false
     */
    protected function getGalleryReadHandler()
    {
        if (is_null($this->galleryReadHandler)) {
            if (class_exists('\\Magento\\Catalog\\Model\\Product\\Gallery\\ReadHandler')) {
                $this->logger->debug('PureClarity: ReadHandler class exists.');

                //using object manager here for backward compatibility issues
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $this->galleryReadHandler = $objectManager->create('\Magento\Catalog\Model\Product\Gallery\ReadHandler');
                $this->logger->debug('PureClarity: Have created ReadHandler.');
            } else {
                $this->logger->debug('PureClarity: ReadHandler class does not exist.');
                $this->galleryReadHandler = false;
            }
        }
        return $this->galleryReadHandler;
    }
}
