<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\ProductExport\PriceHandler;
use Pureclarity\Core\Model\ProductExport\Images;
use Magento\Directory\Helper\Data as DirectoryHelperData;

/**
 * Class ProductExport
 *
 * PureClarity Product Export Module
 * Used to create product feed that's sent to PureClarity.
 */
class ProductExport
{
    /** @var string[] $selectAttributeTypes */
    private $selectAttributeTypes = [
        'select',
        'multiselect',
        'boolean'
    ];

    /** @var string $storeId */
    private $storeId;

    /** @var string $baseCurrencyCode  */
    private $baseCurrencyCode;

    /** @var array $currenciesToProcess */
    private $currenciesToProcess = [];

    /** @var array[] $attributesToInclude */
    private $attributesToInclude = [];

    /** @var bool[] $seenProductIds */
    private $seenProductIds = [];

    /** @var Store $currentStore */
    private $currentStore = null;

    /** @var string[] $brandLookup */
    private $brandLookup = [];

    /** @var string[] $categoryCollection */
    private $categoryCollection = [];

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var StoreFactory $storeFactory */
    private $storeFactory;

    /** @var CurrencyFactory $directoryCurrencyFactory */
    private $directoryCurrencyFactory;

    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var FeedFactory $coreFeedFactory */
    private $coreFeedFactory;

    /** @var ProductAttributeCollectionFactory $productAttributeCollectionFactory */
    private $productAttributeCollectionFactory;

    /** @var ProductCollectionFactory $productCollectionFactory */
    private $productCollectionFactory;

    /** @var CategoryCollectionFactory $categoryCollectionFactory */
    private $categoryCollectionFactory;

    /** @var StockRegistryInterface $stockRegistry */
    private $stockRegistry;

    /** @var ConfigurableFactory $productTypeConfigurableFactory */
    private $productTypeConfigurableFactory;

    /** @var DirectoryHelperData $directoryHelper */
    private $directoryHelper;

    /** @var BlockFactory $blockFactory */
    private $blockFactory;

    /** @var PriceHandler $corePriceHandler */
    private $corePriceHandler;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Images $productImages */
    private $productImages;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StoreFactory $storeFactory
     * @param CurrencyFactory $directoryCurrencyFactory
     * @param Data $coreHelper
     * @param FeedFactory $coreFeedFactory
     * @param ProductAttributeCollectionFactory $productAttributeCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param StockRegistryInterface $stockRegistry
     * @param ConfigurableFactory $productTypeConfigurableFactory
     * @param DirectoryHelperData $directoryHelper
     * @param BlockFactory $blockFactory
     * @param PriceHandler $corePriceHandler
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     * @param Images $productImages
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StoreFactory $storeFactory,
        CurrencyFactory $directoryCurrencyFactory,
        Data $coreHelper,
        FeedFactory $coreFeedFactory,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        StockRegistryInterface $stockRegistry,
        ConfigurableFactory $productTypeConfigurableFactory,
        DirectoryHelperData $directoryHelper,
        BlockFactory $blockFactory,
        PriceHandler $corePriceHandler,
        LoggerInterface $logger,
        CoreConfig $coreConfig,
        Images $productImages
    ) {
        $this->storeManager                      = $storeManager;
        $this->storeFactory                      = $storeFactory;
        $this->directoryCurrencyFactory          = $directoryCurrencyFactory;
        $this->coreHelper                        = $coreHelper;
        $this->coreFeedFactory                   = $coreFeedFactory;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->productCollectionFactory          = $productCollectionFactory;
        $this->categoryCollectionFactory         = $categoryCollectionFactory;
        $this->stockRegistry                     = $stockRegistry;
        $this->productTypeConfigurableFactory    = $productTypeConfigurableFactory;
        $this->directoryHelper                   = $directoryHelper;
        $this->blockFactory                      = $blockFactory;
        $this->corePriceHandler                  = $corePriceHandler;
        $this->logger                            = $logger;
        $this->coreConfig                        = $coreConfig;
        $this->productImages                     = $productImages;
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
        if ($this->coreConfig->isBrandFeedEnabled($this->storeId)) {
            $feedModel = $this->coreFeedFactory->create();
            $this->brandLookup = $feedModel->BrandFeedArray($this->storeId);
        }

        // Get Attributes
        $attributes = $this->productAttributeCollectionFactory->create()->getItems();
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
        $categoryCollection = $this->categoryCollectionFactory->create()
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

        $products = $this->productCollectionFactory->create()
            ->setStoreId($this->storeId)
            ->addStoreFilter($this->storeId)
            ->addUrlRewrite()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter("status", ["eq" => Status::STATUS_ENABLED])
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
        // Check hash that we've not already seen this product
        if (!array_key_exists($product->getId(), $this->seenProductIds) ||
            $this->seenProductIds[$product->getId()] === null
        ) {
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
            
            $productImageUrl = $this->productImages->getProductImageUrl($product, $this->currentStore);
            $allImages = $this->productImages->getProductGalleryUrls($product);

            // Set standard data
            $data = [
                "_index" => $index,
                "Id" => $product->getId(),
                "Sku" => $product->getSku(),
                "Title" => $product->getName(),
                "Description" => [
                    strip_tags($product->getData('description')),
                    strip_tags($product->getShortDescription())
                ],
                "Link" => $productUrl,
                "Image" => $productImageUrl,
                "Categories" => $categoryIds,
                "MagentoCategories" => array_values(array_unique($categoryList, SORT_STRING)),
                "MagentoProductType" => $product->getTypeId(),
                "InStock" => $this->stockRegistry->getStockItem($product->getId())->getIsInStock() ? 'true' : 'false'
            ];

            if (count($allImages) > 0) {
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
                    $childIds = $this->productTypeConfigurableFactory->create()
                        ->getChildrenIds($product->getId());
                    if (count($childIds[0]) > 0) {
                        $childProducts = $this->productCollectionFactory->create()
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
                    $childProducts = $product->getTypeInstance(true)
                                            ->getSelectionsCollection(
                                                $product->getTypeInstance(true)->getOptionsIds($product),
                                                $product
                                            );
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
        $priceData = $this->corePriceHandler->getProductPrices(
            $this->currentStore,
            $product,
            true,
            $childProducts
        );
        
        $prices = [];
        $salePrices = [];
        $groupPrices = [];
        
        foreach ($this->currenciesToProcess as $currency) {
            // Process currency for min price
            $basePrices = $this->preparePriceData($priceData['base'], $currency);
            $prices = array_merge($prices, $basePrices['Prices']);
            if (!empty($basePrices['SalePrices'])) {
                $salePrices = array_merge($salePrices, $basePrices['SalePrices']);
            }
            
            if (isset($priceData['group'])) {
                foreach ($priceData['group'] as $groupId => $groupPriceData) {
                    $basePrices = $this->preparePriceData($groupPriceData, $currency);
                    
                    if (!isset($groupPrices[$groupId])) {
                        $groupPrices[$groupId] = [
                            'Prices' => [],
                            'SalePrices' => []
                        ];
                    }
                    
                    $groupPrices[$groupId]['Prices'] = array_merge(
                        $groupPrices[$groupId]['Prices'],
                        $basePrices['Prices']
                    );
                    
                    $groupPrices[$groupId]['SalePrices'] = array_merge(
                        $groupPrices[$groupId]['SalePrices'],
                        $basePrices['SalePrices']
                    );
                }
            }
        }
        
        $data['Prices'] = $prices;
        $data['SalePrices'] = $salePrices;
        $data['GroupPrices'] = $groupPrices;
    }
    
    /**
     * Checks product pricing data and returns prices that need to be added to the feed
     *
     * @param mixed[] $priceData
     * @param string $currency
     *
     * @return array
     */
    private function preparePriceData(array $priceData, string $currency)
    {
        $prices = [
            'Prices' => [],
            'SalePrices' => []
        ];
        
        // Process currency for min price
        $minPrice = $this->convertCurrency($priceData['min'], $currency);
        $minFinalPrice = $this->convertCurrency($priceData['min-final'], $currency);
        $prices['Prices'][] = number_format($minPrice, 2, '.', '') . ' ' . $currency;
        if ($minFinalPrice !== null && $minFinalPrice < $minPrice) {
            $prices['SalePrices'][] = number_format($minFinalPrice, 2, '.', '') . ' ' . $currency;
        }
        
        // Process currency for max price if it's different to min price
        $maxPrice = $this->convertCurrency($priceData['max'], $currency);
        if ($minPrice < $maxPrice) {
            $prices['Prices'][] = number_format($maxPrice, 2, '.', '') . ' ' . $currency;
            $maxFinalPrice = $this->convertCurrency($priceData['max-final'], $currency);
            if ($maxFinalPrice !== null && $maxFinalPrice < $maxPrice) {
                $prices['SalePrices'][] = number_format($maxFinalPrice, 2, '.', '') . ' ' . $currency;
            }
        }
        
        return $prices;
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
}
