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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Helper\Data;
use Magento\Store\Model\Store;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Registry;
use Magento\Bundle\Pricing\Adjustment\BundleCalculatorInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PriceHandler
 *
 * PureClarity Product Export Price Handler
 * Gets prices to be sent in the data feed
 */
class PriceHandler
{
    const REGISTRY_KEY_CUSTOMER_GROUP = 'pc_bundle_customer_group';
    const CONFIG_PATH_CUSTOMER_GROUPS = 'pureclarity/feeds/product_send_customer_group_pricing';
    
    /** @var string[] */
    private $allCustomerGroupIds;
    
    /** @var RuleFactory */
    private $ruleFactory;
    
    /** @var Collection */
    private $customerGroupCollection;
    
    /** @var Data */
    private $catalogHelper;
    
    /** @var ScopeConfigInterface */
    private $scopeConfig;
    
    /** @var Registry */
    private $registry;
    
    /** @var BundleCalculatorInterfaceFactory */
    private $bundleCalculatorFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var LoggerInterface */
    private $logger;
    
    /**
     * @param RuleFactory $ruleFactory
     * @param Collection $customerGroupCollection
     * @param Data $catalogHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Registry $registry
     * @param BundleCalculatorInterfaceFactory $bundleCalculatorFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        RuleFactory $ruleFactory,
        Collection $customerGroupCollection,
        Data $catalogHelper,
        ScopeConfigInterface $scopeConfig,
        Registry $registry,
        BundleCalculatorInterfaceFactory $bundleCalculatorFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->ruleFactory               = $ruleFactory;
        $this->customerGroupCollection   = $customerGroupCollection;
        $this->catalogHelper             = $catalogHelper;
        $this->scopeConfig               = $scopeConfig;
        $this->registry                  = $registry;
        $this->bundleCalculatorFactory   = $bundleCalculatorFactory;
        $this->storeManager              = $storeManager;
        $this->logger                    = $logger;
    }
    
    /**
     * Gets base prices for the supplied product, but also customer group pricing if enabled
     *
     * @param Store $store
     * @param Product $product
     * @param boolean $includeTax
     * @param Product[] $childProducts
     * @return mixed[]
     */
    public function getProductPrices(
        Store $store,
        Product $product,
        $includeTax = true,
        array $childProducts = null
    ) {
        $this->registry->register(self::REGISTRY_KEY_CUSTOMER_GROUP, null);

        try {
            $currentStore = $this->storeManager->getStore();
            $this->storeManager->setCurrentStore($store->getId());
        } catch (NoSuchEntityException $e) {
            $this->logger->error('PureClarity: cannot load current store:' . $e->getMessage());
        }

        $priceInfo = [
            'base' => $this->getPriceInfo(
                $store,
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
                $priceInfo['group'][$customerGroupId] = $this->getPriceInfo(
                    $store,
                    $product,
                    $customerGroupId,
                    $includeTax,
                    $childProducts
                );
            }
        }

        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_GROUP);

        if (isset($currentStore)) {
            $this->storeManager->setCurrentStore($currentStore->getId());
        }

        return $priceInfo;
    }
    
    /**
     * Gets min and max prices for the supplied product
     *
     * @param Store $store
     * @param Product $product
     * @param string $customerGroupId
     * @param boolean $includeTax
     * @param Product[] $childProducts
     * @return mixed[]
     */
    private function getPriceInfo(
        Store $store,
        Product $product,
        $customerGroupId = null,
        $includeTax = true,
        array $childProducts = null
    ) {
        $product->setStoreId($store->getId());
        $product->setCustomerGroupId($customerGroupId);
        $product->reloadPriceInfo();

        switch ($product->getTypeId()) {
            case BundleType::TYPE_CODE:
                $prices = $this->getBundlePricing($product);
                break;
            case Grouped::TYPE_CODE:
            case Configurable::TYPE_CODE:
                $prices = $this->getChildPricing($store, $childProducts, $customerGroupId, $includeTax);
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
     * @return mixed[]
     */
    private function getBundlePricing(Product $product)
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
     * @param Store $store
     * @param string $customerGroupId
     * @param Product[] $childProducts
     * @param bool $includeTax
     * @return mixed[]
     */
    private function getChildPricing(Store $store, array $childProducts, $customerGroupId = null, $includeTax = true)
    {
        $lowestPrice = 0;
        $highestPrice = 0;
        $lowestFinalPrice = 0;
        $highestFinalPrice = 0;

        foreach ($childProducts as $associatedProduct) {
            if (!$associatedProduct->isDisabled()) {
                //base prices
                $variationPrices = $this->getPriceInfo(
                    $store,
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
     * @param string $customerGroupId
     * @param float $price
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
