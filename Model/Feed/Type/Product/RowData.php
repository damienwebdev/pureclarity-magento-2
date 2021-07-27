<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Product;

use Pureclarity\Core\Api\ProductFeedRowDataManagementInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Images;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Categories;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Swatches;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Brand;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Stock;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Attributes;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Children;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Prices;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Exception;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class RowData
 *
 * Handles individual product data rows in the feed
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RowData implements ProductFeedRowDataManagementInterface
{
    /** @var string[] */
    private $selectAttributeTypes = [
        'select',
        'multiselect',
        'boolean'
    ];

    /** @var LoggerInterface */
    private $logger;

    /** @var Images */
    private $images;

    /** @var Categories */
    private $categories;

    /** @var Swatches */
    private $swatches;

    /** @var Brand */
    private $brand;

    /** @var Stock */
    private $stock;

    /** @var Attributes */
    private $attributes;

    /** @var Children */
    private $children;

    /** @var Prices */
    private $prices;

    /**
     * @param LoggerInterface $logger
     * @param Images $images
     * @param Categories $categories
     * @param Swatches $swatches
     * @param Brand $brand
     * @param Stock $stock
     * @param Attributes $attributes
     * @param Children $children
     * @param Prices $prices
     */
    public function __construct(
        LoggerInterface $logger,
        Images $images,
        Categories $categories,
        Swatches $swatches,
        Brand $brand,
        Stock $stock,
        Attributes $attributes,
        Children $children,
        Prices $prices
    ) {
        $this->logger     = $logger;
        $this->images     = $images;
        $this->categories = $categories;
        $this->swatches   = $swatches;
        $this->brand      = $brand;
        $this->stock      = $stock;
        $this->attributes = $attributes;
        $this->children   = $children;
        $this->prices     = $prices;
    }

    /**
     * Builds the product data for the product feed.
     * @param StoreInterface $store
     * @param ProductInterface|Product $row
     * @return array
     */
    public function getRowData(StoreInterface $store, $row): array
    {
        try {
            $this->logger->debug(
                'Product feed: ' . 'Processing product ' . $row->getId() . ' (' . $row->getSku() . ')'
            );
            $data = $this->getBaseData($store, $row);
            $this->addImageData($store, $row, $data);
            $this->addCategoryData($row, $data);
            $this->addSwatchData($store, $row, $data);
            $this->addVisibilityData($row, $data);
            $this->addBrandData($store, $row, $data);
            $this->addPureClarityAttributes($store, $row, $data);
            $this->addStockData($store, $row, $data);
            $this->addAttributeData($store, $row, $data);
            $childProducts = $this->children->loadChildData($row);
            $this->logger->debug('Product feed: ' . count($childProducts) . ' child products to process');
            $this->addChildData($store, $childProducts, $data);
            $this->addPriceData($store, $row, $data, $childProducts);
        } catch (Exception $e) {
            $data = [];
            $this->logger->error(
                'PureClarity: could not add Product ' . $row->getId()
                . ' (' . $row->getName() . '): '
                . $e->getMessage()
            );
        }

        $this->logger->debug('Product feed: Product Data - ' . var_export($data, true));

        return $data;
    }

    /**
     * Returns the core information about a product
     *
     * @param StoreInterface $store
     * @param ProductInterface|Product $product
     * @return array
     */
    public function getBaseData(StoreInterface $store, $product): array
    {
        return [
            'Id' => $product->getId(),
            'Sku' => $product->getSku(),
            'Title' => $product->getName(),
            'Description' => [
                strip_tags((string)$product->getData('description')),
                strip_tags((string)$product->getShortDescription())
            ],
            'Link' => $this->getProductUrl($product, $store),
            'Image' => '',
            'Categories' => [],
            'MagentoCategories' => [],
            'MagentoProductType' => $product->getTypeId(),
            'InStock' => ''
        ];
    }

    /**
     * Returns a Product's URL for the given store.
     *
     * @param ProductInterface|Product $product
     * @param StoreInterface $store
     * @return string
     */
    public function getProductUrl($product, StoreInterface $store): string
    {
        $productUrl = '';
        $product->setStoreId((int)$store->getId());
        $productUrlModel = $product->getUrlModel();

        if ($productUrlModel) {
            $urlParams = [
                '_nosid' => true,
                '_scope' => (int)$store->getId()
            ];

            $productUrl = $productUrlModel->getUrl($product, $urlParams);

            if ($productUrl) {
                $productUrl = str_replace(["https:", "http:"], "", $productUrl);
            }
        }

        return $productUrl;
    }

    /**
     * Adds Product Image data to the given data array
     *
     * @param StoreInterface $store
     * @param ProductInterface|Product $product
     * @param array $data
     */
    public function addImageData(StoreInterface $store, $product, array &$data): void
    {
        $data['Image'] = $this->images->getProductImageUrl($product, $store);

        $allImages = $this->images->getProductGalleryUrls($product);
        if (count($allImages) > 0) {
            $data['AllImages'] = $allImages;
        }
    }

    /**
     * Adds Product Category data to the given data array
     *
     * @param ProductInterface|Product $product
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    public function addCategoryData($product, array &$data): void
    {
        $categoryData = $this->categories->getCategoryData($product);
        $data['Categories'] = $categoryData['Categories'];
        $data['MagentoCategories'] = $categoryData['MagentoCategories'];
    }

    /**
     * Adds Product Swatch data to the given data array
     *
     * @param StoreInterface $store
     * @param ProductInterface|Product $product
     * @param mixed[] $data
     */
    public function addSwatchData(StoreInterface $store, $product, array &$data): void
    {
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            $swatchData = $this->swatches->getSwatchData($store, $product);
            $data['jsonconfig'] = $swatchData['jsonconfig'];
            $data['swatchrenderjson'] = $swatchData['swatchrenderjson'];
        }
    }

    /**
     * Adds Product Visibility data to the given data array
     *
     * @param ProductInterface|Product $product
     * @param array $data
     * @return void
     */
    public function addVisibilityData($product, array &$data): void
    {
        $visibility = $product->getVisibility();
        if ($visibility === Visibility::VISIBILITY_IN_CATALOG) {
            $data['ExcludeFromSearch'] = true;
        } elseif ($visibility === Visibility::VISIBILITY_IN_SEARCH) {
            $data['ExcludeFromProductListing'] = true;
        }
    }

    /**
     * Adds Product Brand data to the given data array
     *
     * @param StoreInterface $store
     * @param ProductInterface|Product $product
     * @param array $data
     * @return void
     * @throws NoSuchEntityException
     */
    public function addBrandData(StoreInterface $store, $product, array &$data): void
    {
        $brandId = $this->brand->getBrandId((int)$store->getId(), $product);

        if ($brandId) {
            $data['Brand'] = $brandId;
        }
    }

    /**
     * Adds PureClarity Product attribute data to the given data array
     *
     * @param StoreInterface $store
     * @param ProductInterface|Product $product
     * @param array $data
     * @return void
     */
    public function addPureClarityAttributes(StoreInterface $store, $product, array &$data): void
    {
        $searchTags = $product->getData('pureclarity_search_tags');
        if (!empty($searchTags)) {
            $data['SearchTags'] = $this->processSearchTags($searchTags);
        }

        $overlayImage = (string)$product->getData('pureclarity_overlay_image');
        if ($overlayImage !== '') {
            $overlayImage = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $overlayImage;
            $data['ImageOverlay'] = str_replace(['https:', 'http:'], '', $overlayImage);
        }

        if ($product->getData('pureclarity_exc_rec') === '1') {
            $this->logger->debug('Product feed: Product excluded from recommenders due to flag on product');
            $data['ExcludeFromRecommenders'] = true;
        }

        if ($product->getData('pureclarity_newarrival') === '1') {
            $data['NewArrival'] = true;
        }

        if ($product->getData('pureclarity_onoffer') === '1') {
            $data['OnOffer'] = true;
        }
    }

    /**
     * Processes given search tag string into an array
     *
     * @param string $searchTags
     * @return array
     */
    public function processSearchTags(string $searchTags): array
    {
        $filteredTags = [];
        if (!empty($searchTags)) {
            $tags = explode(',', $searchTags);
            if (is_array($tags)) {
                $filteredTags = array_filter($tags);
            }
        }

        return $filteredTags;
    }

    /**
     * Adds Stock data to the given data array
     *
     * @param StoreInterface $store
     * @param ProductInterface|Product $product
     * @param array $data
     * @return void
     */
    public function addStockData(StoreInterface $store, $product, array &$data): void
    {
        $data['InStock'] = $this->stock->getStockFlag($product);

        if ($this->stock->isExcluded((int)$store->getId(), $data['InStock'])) {
            $this->logger->debug('Product feed: Product excluded from recommenders due to being out of stock');
            $data["ExcludeFromRecommenders"] = true;
        }
    }

    /**
     * Adds Product attribute data to the given data array
     *
     * @param StoreInterface $store
     * @param $product
     * @param array $data
     */
    public function addAttributeData(StoreInterface $store, $product, array &$data): void
    {
        foreach ($this->attributes->loadAttributes((int)$store->getId()) as $attribute) {
            $code = $attribute['code'];
            $name = $attribute['label'];

            if ($product->getData($code) !== null) {
                try {
                    if (in_array($attribute['type'], $this->selectAttributeTypes, true)) {
                        $attrValue = $product->getAttributeText($code);
                    } else {
                        $attrValue = $product->getData($code);
                    }
                } catch (Exception $e) {
                    // Unable to read attribute text, just skip this attribute
                    continue;
                }

                if (is_array($attrValue)) {
                    foreach ($attrValue as $value) {
                        $this->addValueToDataArray($data, $name, $value);
                    }
                } elseif ($attrValue !== null) {
                    $this->addValueToDataArray($data, $name, $attrValue);
                }
            }
        }
    }

    /**
     * Process Child Product related data to the given data array
     *
     * @param StoreInterface $store
     * @param array $childProducts
     * @param array $data
     * @return void
     */
    public function addChildData(StoreInterface $store, array $childProducts, array &$data): void
    {
        foreach ($childProducts as $product) {
            $this->addChildProductData($product, $data);
            $this->addAttributeData($store, $product, $data);
        }
    }

    /**
     * Adds Child Product related data to the given data array
     *
     * @param $product
     * @param $data
     */
    public function addChildProductData($product, &$data): void
    {
        $this->addValueToDataArray($data, 'AssociatedSkus', $product->getData('sku'));
        $this->addValueToDataArray($data, 'AssociatedTitles', $product->getData('name'));
        $this->addValueToDataArray($data, 'Description', strip_tags((string)$product->getData('description')));
        $this->addValueToDataArray($data, 'Description', strip_tags((string)$product->getShortDescription()));
        $searchTags = (string)$product->getData('pureclarity_search_tags');
        if ($searchTags !== '') {
            $tags = $this->processSearchTags($searchTags);
            foreach ($tags as $tag) {
                $this->addValueToDataArray($data, 'SearchTags', $tag);
            }
        }
    }

    /**
     * Adds Pricing data to the given data array
     *
     * @param StoreInterface $store
     * @param Product|ProductInterface $product
     * @param array $data
     * @param array $childProducts
     * @throws NoSuchEntityException
     */
    public function addPriceData(StoreInterface $store, $product, array &$data, array $childProducts): void
    {
        $priceData = $this->prices->getPriceData(
            $store,
            $product,
            $childProducts
        );

        $data['Prices'] = $priceData['Prices'];
        $data['SalePrices'] = $priceData['SalePrices'];
        $data['GroupPrices'] = $priceData['GroupPrices'];
    }

    /**
     * Adds a given value to the data array on the given key.
     *
     * @param mixed[] $data
     * @param string $key
     * @param mixed $value
     */
    private function addValueToDataArray(array &$data, string $key, $value): void
    {
        if (!array_key_exists($key, $data)) {
            $data[$key][] = $value;
        } elseif ($value !== null) {
            if (!is_array($data[$key])) {
                $data[$key] = [
                    $data[$key],
                    $value
                ];
            } elseif (!in_array($value, $data[$key], true)) {
                $data[$key][] = $value;
            }
        }
    }
}
