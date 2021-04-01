<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Helper\Data as DirectoryHelperData;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Helper\Data;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\Registry;
use Magento\Bundle\Pricing\Adjustment\BundleCalculatorInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class Prices
 *
 * PureClarity Product Export Price Handler
 * Gets prices to be sent in the data feed
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Prices
{
    /** @var string */
    public const REGISTRY_KEY_CUSTOMER_GROUP = 'pc_bundle_customer_group';

    /** @var array */
    private $currenciesToProcess;

    /** @var string[] $allCustomerGroupIds */
    private $allCustomerGroupIds;

    /** @var RuleFactory $ruleFactory */
    private $ruleFactory;

    /** @var CollectionFactory $collectionFactory */
    private $collectionFactory;

    /** @var Data $catalogHelper */
    private $catalogHelper;

    /** @var Registry $registry */
    private $registry;

    /** @var BundleCalculatorInterfaceFactory $calculatorFactory */
    private $calculatorFactory;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var CurrencyFactory */
    private $currencyFactory;

    /** @var DirectoryHelperData */
    private $directoryHelper;

    /**
     * @param RuleFactory $ruleFactory
     * @param CollectionFactory $collectionFactory
     * @param Data $catalogHelper
     * @param Registry $registry
     * @param BundleCalculatorInterfaceFactory $calculatorFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     * @param CurrencyFactory $currencyFactory
     * @param DirectoryHelperData $directoryHelper
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        RuleFactory $ruleFactory,
        CollectionFactory $collectionFactory,
        Data $catalogHelper,
        Registry $registry,
        BundleCalculatorInterfaceFactory $calculatorFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        CoreConfig $coreConfig,
        CurrencyFactory $currencyFactory,
        DirectoryHelperData $directoryHelper
    ) {
        $this->ruleFactory       = $ruleFactory;
        $this->collectionFactory = $collectionFactory;
        $this->catalogHelper     = $catalogHelper;
        $this->registry          = $registry;
        $this->calculatorFactory = $calculatorFactory;
        $this->storeManager      = $storeManager;
        $this->logger            = $logger;
        $this->coreConfig        = $coreConfig;
        $this->currencyFactory   = $currencyFactory;
        $this->directoryHelper   = $directoryHelper;
    }

    /**
     * @param StoreInterface $store
     * @param Product|ProductInterface $product
     * @param array $childProducts
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPriceData(StoreInterface $store, $product, $childProducts = []): array
    {
        $priceData = $this->getProductPrices(
            $store,
            $product,
            true,
            $childProducts
        );

        $prices = [];
        $salePrices = [];
        $groupPrices = [];

        $baseCurrencyCode = $store->getBaseCurrencyCode();

        foreach ($this->getCurrencies($baseCurrencyCode, $store) as $currency) {
            // Process currency for min price
            $basePrices = $this->preparePriceData($priceData['base'], $currency, $baseCurrencyCode);
            $this->imitateMerge($prices, $basePrices['Prices']);
            if (!empty($basePrices['SalePrices'])) {
                $this->imitateMerge($salePrices, $basePrices['SalePrices']);
            }

            if (isset($priceData['group'])) {
                foreach ($priceData['group'] as $groupId => $groupPriceData) {
                    $basePrices = $this->preparePriceData($groupPriceData, $currency, $baseCurrencyCode);

                    if (!isset($groupPrices[$groupId])) {
                        $groupPrices[$groupId] = [
                            'Prices' => [],
                            'SalePrices' => []
                        ];
                    }

                    $this->imitateMerge($groupPrices[$groupId]['Prices'], $basePrices['Prices']);
                    $this->imitateMerge($groupPrices[$groupId]['SalePrices'], $basePrices['SalePrices']);
                }
            }
        }

        return [
            'Prices' => $prices,
            'SalePrices' => $salePrices,
            'GroupPrices' => $groupPrices
        ];
    }

    /**
     * Checks product pricing data and returns prices that need to be added to the feed
     *
     * @param mixed[] $priceData
     * @param string $currency
     * @param string $baseCurrencyCode
     * @return array
     * @throws NoSuchEntityException
     */
    private function preparePriceData(array $priceData, string $currency, string $baseCurrencyCode): array
    {
        $prices = [
            'Prices' => [],
            'SalePrices' => []
        ];

        // Process currency for min price
        $minPrice = $this->convertCurrency($priceData['min'], $baseCurrencyCode, $currency);
        $minFinalPrice = $this->convertCurrency($priceData['min-final'], $baseCurrencyCode, $currency);
        $prices['Prices'][] = number_format($minPrice, 2, '.', '') . ' ' . $currency;
        if ($minFinalPrice !== null && $minFinalPrice < $minPrice) {
            $prices['SalePrices'][] = number_format($minFinalPrice, 2, '.', '') . ' ' . $currency;
        }

        // Process currency for max price if it's different to min price
        $maxPrice = $this->convertCurrency($priceData['max'], $baseCurrencyCode, $currency);
        if ($minPrice < $maxPrice) {
            $prices['Prices'][] = number_format($maxPrice, 2, '.', '') . ' ' . $currency;
            $maxFinalPrice = $this->convertCurrency($priceData['max-final'], $baseCurrencyCode, $currency);
            if ($maxFinalPrice !== null && $maxFinalPrice < $maxPrice) {
                $prices['SalePrices'][] = number_format($maxFinalPrice, 2, '.', '') . ' ' . $currency;
            }
        }

        return $prices;
    }

    /**
     * Gets base prices for the supplied product, but also customer group pricing if enabled
     *
     * @param StoreInterface $store
     * @param Product|ProductInterface $product
     * @param boolean $includeTax
     * @param Product[] $childProducts
     * @return mixed[]
     */
    private function getProductPrices(
        StoreInterface $store,
        $product,
        $includeTax = true,
        array $childProducts = null
    ): array {
        $this->registry->register(self::REGISTRY_KEY_CUSTOMER_GROUP, null);

        try {
            $currentStore = $this->storeManager->getStore();
            $this->storeManager->setCurrentStore((int)$store->getId());
        } catch (NoSuchEntityException $e) {
            $this->logger->error('PureClarity: cannot load current store: ' . $e->getMessage());
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

        if ($this->coreConfig->sendCustomerGroupPricing((int)$store->getId())) {
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
            $this->storeManager->setCurrentStore((int)$currentStore->getId());
        }

        return $priceInfo;
    }

    /**
     * Gets min and max prices for the supplied product
     *
     * @param StoreInterface $store
     * @param Product|ProductInterface $product
     * @param string $customerGroupId
     * @param boolean $includeTax
     * @param Product[] $childProducts
     * @return mixed[]
     */
    private function getPriceInfo(
        StoreInterface $store,
        $product,
        $customerGroupId = null,
        $includeTax = true,
        array $childProducts = null
    ): array {
        $product->setStoreId((int)$store->getId());
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
     * @param Product|ProductInterface $product
     * @return mixed[]
     */
    private function getBundlePricing($product): array
    {
        $calculator = $this->calculatorFactory->create();

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
     * @param StoreInterface $store
     * @param string $customerGroupId
     * @param Product[] $childProducts
     * @param bool $includeTax
     * @return mixed[]
     */
    private function getChildPricing(
        StoreInterface $store,
        array $childProducts,
        $customerGroupId = null,
        bool $includeTax = true
    ): array {
        $lowestPrice = 0;
        $highestPrice = 0;
        $lowestFinalPrice = 0;
        $highestFinalPrice = 0;

        foreach ($childProducts as $associatedProduct) {
            if ($associatedProduct->isDisabled()) {
                continue;
            }
            //base prices
            $variationPrices = $this->getPriceInfo(
                $store,
                $associatedProduct,
                $customerGroupId,
                $includeTax
            );

            $lowestPrice = $this->getLowestValue($lowestPrice, $variationPrices['min']);
            $highestPrice = $this->getHighestValue($highestPrice, $variationPrices['max']);
            $lowestFinalPrice = $this->getLowestValue($lowestFinalPrice, $variationPrices['min-final']);
            $highestFinalPrice = $this->getHighestValue($highestFinalPrice, $variationPrices['max-final']);
        }

        return [
            'min' => $lowestPrice,
            'min-final' => $lowestFinalPrice,
            'max' => $highestPrice,
            'max-final' => $highestFinalPrice
        ];
    }

    /**
     * Works out the lowest value of 2 values
     * @param int|float $valueA
     * @param int|float $valueB
     * @return int|float
     */
    private function getLowestValue($valueA, $valueB)
    {
        if ($valueA === 0 || $valueB < $valueA) {
            return $valueB;
        }

        return $valueA;
    }

    /**
     * Works out the highest value of 2 values
     * @param int|float $valueA
     * @param int|float $valueB
     * @return int|float
     */
    private function getHighestValue($valueA, $valueB)
    {
        if ($valueA === 0 || $valueB > $valueA) {
            return $valueB;
        }

        return $valueA;
    }

    /**
     * Gets prices for Simple Products
     *
     * @param Product|ProductInterface $product
     * @param string $customerGroupId
     * @return mixed[]
     */
    private function getSimplePricing(Product $product, $customerGroupId = null): array
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
     * @param Product|ProductInterface $product
     * @param string $customerGroupId
     * @param float $price
     * @return float|null
     */
    private function getSalePrice($product, $customerGroupId, float $price): ?float
    {
        $rule = $this->ruleFactory->create();
        $product->setCustomerGroupId($customerGroupId);

        return $rule->calcProductPriceRule(
            $product,
            $price
        );
    }

    /**
     * Gets all customer group IDs
     *
     * @return string[];
     */
    private function getAllCustomerGroupIds(): array
    {
        if (!isset($this->allCustomerGroupIds)) {
            $collection = $this->collectionFactory->create();
            $this->allCustomerGroupIds = $collection->getAllIds();
        }
        return $this->allCustomerGroupIds;
    }

    /**
     * Imitates array_merge for numerical key arrays
     * Reason: array_merge is much slower than this.
     * @param array $array1
     * @param array $array2
     */
    private function imitateMerge(array &$array1, array $array2): void
    {
        foreach ($array2 as $i) {
            $array1[] = $i;
        }
    }

    /**
     * Converts a float from the base currency tot he given currency code.
     * @param float $price
     * @param string $baseCurrencyCode
     * @param string $toCode
     * @return float
     * @throws NoSuchEntityException
     */
    private function convertCurrency(float $price, string $baseCurrencyCode, string $toCode): float
    {
        if ($toCode === $baseCurrencyCode) {
            return $price;
        }
        return $this->directoryHelper->currencyConvert($price, $baseCurrencyCode, $toCode);
    }

    /**
     * Gets all available currencies for a given store.
     * @param string $baseCurrencyCode
     * @param StoreInterface $store
     * @return array
     */
    private function getCurrencies(string $baseCurrencyCode, StoreInterface $store): array
    {
        if ($this->currenciesToProcess === null) {
            $this->currenciesToProcess = [];
            $currencyModel = $this->currencyFactory->create();
            $currencies = $store->getAllowedCurrencies();
            $currencyRates = $currencyModel->getCurrencyRates($baseCurrencyCode, array_values($currencies));
            $this->currenciesToProcess[] = $baseCurrencyCode;
            if ($currencyRates !== null) {
                foreach ($currencies as $currency) {
                    if ($currency !== $baseCurrencyCode && !empty($currencyRates[$currency])) {
                        $this->currenciesToProcess[] = $currency;
                    }
                }
            }
        }
        return $this->currenciesToProcess;
    }
}
