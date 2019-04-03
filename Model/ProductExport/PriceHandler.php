<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\ProductExport;

use Magento\CatalogRule\Model\RuleFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Helper\Data;
use Magento\Store\Model\Store;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Registry;
use Magento\Bundle\Pricing\Adjustment\BundleCalculatorInterfaceFactory;

/**
 * PureClarity Product Export Price Handler
 * Gets prices to be sent in the data feed
 */
class PriceHandler
{
    public const REGISTRY_KEY_CUSTOMER_GROUP = 'pc_bundle_customer_group';
    private const CONFIG_PATH_CUSTOMER_GROUPS = 'pureclarity/feeds/product_send_customer_group_pricing';
    
    /** @var string[] */
    private $allCustomerGroupIds;
    
    /** @var \Magento\CatalogRule\Model\RuleFactory */
    private $ruleFactory;
    
    /** @var \Magento\Customer\Model\ResourceModel\Group\Collection */
    private $customerGroupCollection;
    
    /** @var \Magento\Catalog\Helper\Data */
    private $catalogHelper;
    
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;
    
    /** @var \Magento\Framework\Registry */
    private $registry;
    
    /** @var \Magento\Bundle\Pricing\Adjustment\BundleCalculatorInterfaceFactory */
    private $bundleCalculatorFactory;
    
    /**
     * @param RuleFactory $ruleFactory
     * @param Collection $customerGroupCollection
     * @param Data $catalogHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Registry $registry
     * @param BundleCalculatorInterfaceFactory $bundleCalculatorFactory
     */
    public function __construct(
        RuleFactory $ruleFactory,
        Collection $customerGroupCollection,
        Data $catalogHelper,
        ScopeConfigInterface $scopeConfig,
        Registry $registry,
        BundleCalculatorInterfaceFactory $bundleCalculatorFactory
    ) {
        $this->ruleFactory               = $ruleFactory;
        $this->customerGroupCollection   = $customerGroupCollection;
        $this->catalogHelper             = $catalogHelper;
        $this->scopeConfig               = $scopeConfig;
        $this->registry                  = $registry;
        $this->bundleCalculatorFactory   = $bundleCalculatorFactory;
    }
    
    /**
     * Gets base prices for the supplied product, but also customer group pricing if enabled
     *
     * @param Store $store
     * @param Product $product
     * @param boolean $includeTax
     * @param Product[] $childProducts
     * @return void
     */
    public function getProductPrices(
        Store $store,
        Product $product,
        bool $includeTax = true,
        array $childProducts = null
    ) {
        $this->registry->register(self::REGISTRY_KEY_CUSTOMER_GROUP, null);
        $priceInfo = [
            'base' => $this->getPriceInfo(
                $product,
                null,
                $includeTax,
                $childProducts
            )
        ];
        
        if ($this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_CUSTOMER_GROUPS,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        )) {
            foreach ($this->getAllCustomerGroupIds() as $customerGroupId) {
                $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_GROUP);
                $this->registry->register(self::REGISTRY_KEY_CUSTOMER_GROUP, $customerGroupId);
                $priceInfo['Group_' . $customerGroupId] = $this->getPriceInfo(
                    $product,
                    $customerGroupId,
                    $includeTax,
                    $childProducts
                );
            }
        }
        
        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_GROUP);
        
        return $priceInfo;
    }
    
    /**
     * Gets min and max prices for the supplied product
     *
     * @param Product $product
     * @param string $customerGroupId
     * @param boolean $includeTax
     * @param Product[] $childProducts
     * @return mixed[]
     */
    private function getPriceInfo(
        Product $product,
        $customerGroupId = null,
        bool $includeTax = true,
        array $childProducts = null
    ) {
        $product->setCustomerGroupId($customerGroupId);
        $product->reloadPriceInfo();
        
        switch ($product->getTypeId()) {
            case BundleType::TYPE_CODE:
                $prices = $this->getBundlePricing($product, $customerGroupId);
                break;
            case Grouped::TYPE_CODE:
            case Configurable::TYPE_CODE:
                $prices = $this->getChildPricing($childProducts, $customerGroupId, $includeTax);
                break;
            default:
                $prices = $this->getSimplePricing($product, $customerGroupId);
                break;
        }
        
        if ($includeTax && $product->getTypeId() !== Configurable::TYPE_CODE) {
            foreach ($prices as $key => $value) {
                $prices[$key] = $this->catalogHelper->getTaxPrice($product, $value, true);
            }
        }
        
        return $prices;
    }
    
    /**
     * Gets prices for Bundle Products
     *
     * @param Product $product
     * @param string $customerGroupId
     * @return mixed[]
     */
    private function getBundlePricing(Product $product, $customerGroupId = null)
    {
        $calculator = $this->bundleCalculatorFactory->create();
        
        $minPrice = $calculator->getMinRegularAmount(0, $product)->getValue();
        $maxPrice = $calculator->getMaxRegularAmount(0, $product)->getValue();
        $minFinalPrice = $calculator->getAmount(0, $product)->getValue();
        $maxFinalPrice = $calculator->getMaxAmount(0, $product)->getValue();
        
        return [
            'min' => $minPrice,
            'min-final' => $minFinalPrice,
            'max' => $maxPrice,
            'max-final' => $maxFinalPrice,
        ];
    }
    
    /**
     * Gets prices for a Group/Configurable Products
     *
     * @param Product $product
     * @param string $customerGroupId
     * @param Product[] $childProducts
     * @return mixed[]
     */
    private function getChildPricing(array $childProducts, $customerGroupId = null, $includeTax = true)
    {
        $lowestPrice = 0;
        $highestPrice = 0;
        $lowestFinalPrice = 0;
        $highestFinalPrice = 0;
        foreach ($childProducts as $associatedProduct) {
            if (!$associatedProduct->isDisabled()) {
                //base prices
                $variationPrices = $this->getPriceInfo(
                    $associatedProduct,
                    $customerGroupId,
                    $includeTax
                );
                if ($lowestPrice == 0 || $variationPrices['min'] < $lowestPrice) {
                    $lowestPrice = $variationPrices['min'];
                }
                if ($highestPrice == 0 || $variationPrices['max'] > $highestPrice) {
                    $highestPrice = $variationPrices['max'];
                }
                
                //final prices
                if ($lowestFinalPrice == 0 || $variationPrices['min-final'] < $lowestFinalPrice) {
                    $lowestFinalPrice = $variationPrices['min-final'];
                }
                
                if ($highestFinalPrice == 0 || $variationPrices['max-final'] > $highestFinalPrice) {
                    $highestFinalPrice = $variationPrices['max-final'];
                }
            }
        }
        
        return [
            'min' => $lowestPrice,
            'min-final' => $lowestFinalPrice,
            'max' => $highestPrice,
            'max-final' => $highestFinalPrice
        ];
    }
    
    /**
     * Gets prices for Simple Products
     *
     * @param Product $product
     * @param string $customerGroupId
     * @return mixed[]
     */
    private function getSimplePricing(Product $product, $customerGroupId = null)
    {
        $salePrice = $this->getSalePrice(
            $product,
            $customerGroupId,
            $product->getPrice()
        );
        
        $price = $product->getPrice();
        $finalPrice = $product->getFinalPrice(1);
        
        $prices = [];
        $prices['min'] = $price;
        $prices['min-final'] = ($salePrice && $salePrice < $finalPrice) ? $salePrice : $finalPrice;
        $prices['max'] = $prices['min'];
        $prices['max-final'] = $prices['min-final'];
        return $prices;
    }
    
    /**
     * Returns a price if a product is on sale
     *
     * @param Product $product
     * @param integer $product
     * @return float|null
     */
    private function getSalePrice(Product $product, $customerGroupId, $price)
    {
        $rule = $this->ruleFactory->create();
        $product->setCustomerGroupId($customerGroupId);
        $discountedPrice = $rule->calcProductPriceRule(
            $product,
            $price
        );
        
        return $discountedPrice;
    }

    /**
     * Gets all customer group IDs
     *
     * @return string[];
     */
    private function getAllCustomerGroupIds()
    {
        if (!isset($this->allCustomerGroupIds)) {
            $this->allCustomerGroupIds = $this->customerGroupCollection->getAllIds();
        }
        return $this->allCustomerGroupIds;
    }
}
