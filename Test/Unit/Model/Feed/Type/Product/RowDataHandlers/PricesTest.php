<?php
declare(strict_types=1);

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Product\RowDataHandlers;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Prices;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Catalog\Helper\Data;
use Magento\Framework\Registry;
use Magento\Bundle\Pricing\Adjustment\BundleCalculatorInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Helper\Data as DirectoryData;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Bundle\Pricing\Adjustment\BundleCalculatorInterface;
use Magento\CatalogRule\Model\Rule;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Directory\Model\Currency;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Bundle\Model\Product\Type as BundleType;
use ReflectionException;

/**
 * Class PricesTest
 *
 * Tests methods in \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Prices
 *
 * @see \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Prices
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
class PricesTest extends TestCase
{
    /** @var RuleFactory | MockObject */
    private $ruleFactory;
    
    /** @var CollectionFactory | MockObject */
    private $collectionFactory;
    
    /** @var Data | MockObject */
    private $catalogHelper;
    
    /** @var Registry | MockObject */
    private $registry;
    
    /** @var BundleCalculatorInterfaceFactory | MockObject */
    private $calculatorFactory;
    
    /** @var StoreManagerInterface | MockObject */
    private $storeManager;
    
    /** @var LoggerInterface | MockObject */
    private $logger;
    
    /** @var CoreConfig | MockObject */
    private $coreConfig;
    
    /** @var CurrencyFactory | MockObject */
    private $currencyFactory;
    
    /** @var DirectoryData | MockObject */
    private $directoryHelper;
    
    /** @var Prices */
    private $prices;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->ruleFactory = $this->createMock(RuleFactory::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->catalogHelper = $this->createMock(Data::class);
        $this->registry = $this->createMock(Registry::class);
        $this->calculatorFactory = $this->createMock(BundleCalculatorInterfaceFactory::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->currencyFactory = $this->createMock(CurrencyFactory::class);
        $this->directoryHelper = $this->createMock(DirectoryData::class);
        $this->prices = new Prices(
            $this->ruleFactory,
            $this->collectionFactory,
            $this->catalogHelper,
            $this->registry,
            $this->calculatorFactory,
            $this->storeManager,
            $this->logger,
            $this->coreConfig,
            $this->currencyFactory,
            $this->directoryHelper
        );
    }

    /**
     * Sets up loaded currencies and currency converter
     * @param string $baseCurrency
     * @param string[] $currencies
     * @throws ReflectionException
     */
    public function setupCurrencies(string $baseCurrency = 'GBP', array $currencies = ['GBP']): void
    {
        $rates = [];

        foreach ($currencies as $code) {
            $rates[$code] = 1;
        }

        $currency = $this->createMock(Currency::class);

        $currency->expects(self::once())
            ->method('getCurrencyRates')
            ->with($baseCurrency, $currencies)
            ->willReturn($rates);

        $this->currencyFactory->expects(self::once())
            ->method('create')
            ->willReturn($currency);

        $this->directoryHelper->method('currencyConvert')
            ->willReturnCallback(
                function ($price, $baseCurrencyCode, $toCode) {
                    if ($baseCurrencyCode === $toCode) {
                        return $price;
                    }
                    return $price * 2;
                }
            );
    }

    /**
     * Sets up customer group collection
     * @throws ReflectionException
     */
    public function setupCustomerGroups(): void
    {
        $this->coreConfig->method('sendCustomerGroupPricing')
            ->willReturn(true);

        $collection = $this->createMock(Collection::class);

        $collection->expects(self::once())
            ->method('getAllIds')
            ->willReturn([1,2,3]);

        $this->collectionFactory->expects(self::once())
            ->method('create')
            ->willReturn($collection);
    }

    /**
     * Sets up bundle price mocking
     * @param bool $hasSale
     * @throws ReflectionException
     */
    public function setupBundlePricing(bool $hasSale = false): void
    {
        $calculator = $this->createMock(BundleCalculatorInterface::class);
        $minPrice = $this->createMock(AmountInterface::class);
        $minPrice->method('getValue')
            ->willReturn(14.00);

        $minFinalPrice = $this->createMock(AmountInterface::class);
        $minFinalPrice->method('getValue')
            ->willReturn($hasSale ? 12.60 : 14.00);

        $maxPrice = $this->createMock(AmountInterface::class);
        $maxPrice->method('getValue')
            ->willReturn(20.00);

        $maxFinalPrice = $this->createMock(AmountInterface::class);
        $maxFinalPrice->method('getValue')
            ->willReturn($hasSale ? 18.00 : 20.00);

        $calculator->expects(self::once())
            ->method('getMinRegularAmount')
            ->willReturn($minPrice);

        $calculator->expects(self::once())
            ->method('getMaxRegularAmount')
            ->willReturn($maxPrice);

        $calculator->expects(self::once())
            ->method('getAmount')
            ->willReturn($minFinalPrice);

        $calculator->expects(self::once())
            ->method('getMaxAmount')
            ->willReturn($maxFinalPrice);

        $this->calculatorFactory->expects(self::once())
            ->method('create')
            ->willReturn($calculator);
    }

    /**
     * Sets up a product object
     * @param string $type
     * @param float $price
     * @param bool $withGroups
     * @param bool $isDisabled
     * @return Product|MockObject
     * @throws ReflectionException
     */
    public function setupProduct(
        string $type,
        float $price,
        bool $withGroups = false,
        bool $isDisabled = false
    ) {
        $product = $this->createPartialMock(
            Product::class,
            [
                'setStoreId',
                '__call',
                'reloadPriceInfo',
                'getTypeId',
                'getPrice',
                'getFinalPrice',
                'getId',
                'isDisabled'
            ]
        );

        if ($withGroups) {
            $product->expects(self::exactly(4))
                ->method('setStoreId')
                ->with(1);

            $product->expects(self::exactly(4))
                ->method('reloadPriceInfo');

            $product->expects(self::exactly(8))
                ->method('__call')
                ->with('setCustomerGroupId');

            $product->method('getPrice')
                ->willReturnOnConsecutiveCalls(
                    $price,
                    $price,
                    $price + 1.00,
                    $price + 1.00,
                    $price + 2.00,
                    $price + 2.00,
                    $price + 3.00,
                    $price + 3.00
                );

            $product->method('getFinalPrice')
                ->willReturnOnConsecutiveCalls(
                    $price,
                    $price + 1.00,
                    $price + 2.00,
                    $price + 3.00
                );
        } elseif ($isDisabled) {
            $product->method('isDisabled')
                ->willReturn($isDisabled);
        } else {
            $product->expects(self::once())
                ->method('setStoreId')
                ->with(1);

            $product->expects(self::once())
                ->method('reloadPriceInfo');

            if ($type === BundleType::TYPE_CODE || $type === Configurable::TYPE_CODE) {
                $product->expects(self::once())
                    ->method('__call')
                    ->with('setCustomerGroupId');
            } else {
                $product->expects(self::exactly(2))
                    ->method('__call')
                    ->with('setCustomerGroupId');
            }

            $product->method('getPrice')
                ->willReturn($price);

            $product->method('getFinalPrice')
                ->willReturn($price);
        }

        $product->method('getTypeId')
            ->willReturn($type);

        $this->catalogHelper->method('getTaxPrice')
            ->willReturnCallback(
                function ($product, $value, $includingTax) {
                    return $value;
                }
            );

        $product->method('getId')
            ->willReturn(1);

        return $product;
    }

    /**
     * Sets up sale price mocking
     * @param bool $hasSale
     * @throws ReflectionException
     */
    public function setupSalePrice(bool $hasSale = false): void
    {
        $rule = $this->createMock(Rule::class);

        if ($hasSale) {
            $rule->method('calcProductPriceRule')
                ->willReturnCallback(
                    function ($product, $price) {
                        return $price * 0.9;
                    }
                );
        } else {
            $rule->method('calcProductPriceRule')
                ->willReturnCallback(
                    function ($product, $price) {
                        return $price;
                    }
                );
        }

        $this->ruleFactory->method('create')
            ->willReturn($rule);
    }

    /**
     * Sets up a StoreInterface mock
     * @param string $baseCurrency
     * @param array $currencies
     * @param bool $error
     * @return StoreInterface|MockObject
     * @throws ReflectionException
     */
    public function setupStore(
        string $baseCurrency = 'GBP',
        array $currencies = ['GBP'],
        bool $error = false
    ) {
        $store = $this->getMockForAbstractClass(
            StoreInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getId', 'getBaseCurrencyCode', 'getAllowedCurrencies']
        );
        $store->method('getId')
            ->willReturn(1);

        $store->method('getBaseCurrencyCode')
            ->willReturn($baseCurrency);

        $store->method('getAllowedCurrencies')
            ->willReturn($currencies);

        if ($error) {
            $this->storeManager->expects(self::once())
                ->method('getStore')
                ->willThrowException(new NoSuchEntityException(new Phrase('An error')));

            $this->logger->expects(self::once())
                ->method('error')
                ->with('PureClarity: cannot load current store: An error');
        } else {
            $this->storeManager->expects(self::once())
                ->method('getStore')
                ->willReturn($store);

            $this->storeManager->expects(self::exactly(2))
                ->method('setCurrentStore');
        }

        return $store;
    }

    /**
     * Sets up configurable product children
     * @throws ReflectionException
     */
    public function setupConfigurableChildren(): array
    {
        $children = [];
        for ($i = 1; $i <= 4; $i++) {
            $price = $i === 2 ? 19.00 : 15.00 + $i;
            $children[] = $this->setupProduct('simple', $price, false, $i === 4);
        }

        return $children;
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Prices::class, $this->prices);
    }

    /**
     * Tests that getPriceData handles a simple product
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataSimple(): void
    {
        $this->setupCurrencies();
        $this->setupSalePrice();

        $this->registry->expects(self::once())
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct('simple', 17.00);
        $store = $this->setupStore();

        self::assertEquals(
            [
                'Prices' => ['17.00 GBP'],
                'SalePrices' => [],
                'GroupPrices' => []
            ],
            $this->prices->getPriceData($store, $product, [])
        );
    }

    /**
     * Tests that getPriceData handles a store exception
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataSimpleStoreException(): void
    {
        $this->setupCurrencies();
        $this->setupSalePrice();

        $this->registry->expects(self::once())
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct('simple', 17.00);
        $store = $this->setupStore('GBP', ['GBP'], true);

        self::assertEquals(
            [
                'Prices' => ['17.00 GBP'],
                'SalePrices' => [],
                'GroupPrices' => []
            ],
            $this->prices->getPriceData($store, $product, [])
        );
    }

    /**
     * Tests that getPriceData handles a simple product that is on sale
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataSimpleOnSale(): void
    {
        $this->setupCurrencies();
        $this->setupSalePrice(true);

        $this->registry->expects(self::once())
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct('simple', 17.00);
        $store = $this->setupStore();

        self::assertEquals(
            [
                'Prices' => ['17.00 GBP'],
                'SalePrices' => ['15.30 GBP'],
                'GroupPrices' => []
            ],
            $this->prices->getPriceData($store, $product, [])
        );
    }

    /**
     * Tests that getPriceData handles a simple product on a multi-currency store
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataSimpleMultiCurrency(): void
    {
        $this->setupCurrencies('GBP', ['GBP', 'USD']);
        $this->setupSalePrice();

        $this->registry->expects(self::once())
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct('simple', 17.00);
        $store = $this->setupStore('GBP', ['GBP', 'USD']);

        self::assertEquals(
            [
                'Prices' => ['17.00 GBP', '34.00 USD'],
                'SalePrices' => [],
                'GroupPrices' => []
            ],
            $this->prices->getPriceData($store, $product, [])
        );
    }

    /**
     * Tests that getPriceData handles a simple product when on sale on a multi-currency store
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataSimpleOnSaleMultiCurrency(): void
    {
        $this->setupCurrencies('GBP', ['GBP', 'USD']);
        $this->setupSalePrice(true);

        $this->registry->expects(self::once())
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct('simple', 17.00);
        $store = $this->setupStore('GBP', ['GBP', 'USD']);

        self::assertEquals(
            [
                'Prices' => ['17.00 GBP', '34.00 USD'],
                'SalePrices' => ['15.30 GBP', '30.60 USD'],
                'GroupPrices' => []
            ],
            $this->prices->getPriceData($store, $product, [])
        );
    }

    /**
     * Tests that getPriceData handles a simple product with customer group pricing enabled
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataSimpleWithCustomerGroups(): void
    {
        $this->setupCurrencies();
        $this->setupSalePrice();
        $this->setupCustomerGroups();

        $this->registry->expects(self::exactly(4))
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct('simple', 17.00, true);
        $store = $this->setupStore();

        self::assertEquals(
            [
                'Prices' => ['17.00 GBP'],
                'SalePrices' => [],
                'GroupPrices' => [
                    1 => [
                        'Prices' => ['18.00 GBP'],
                        'SalePrices' => [],
                    ],
                    2 => [
                        'Prices' => ['19.00 GBP'],
                        'SalePrices' => [],
                    ],
                    3 => [
                        'Prices' => ['20.00 GBP'],
                        'SalePrices' => [],
                    ]
                ]
            ],
            $this->prices->getPriceData($store, $product, [])
        );
    }

    /**
     * Tests that getPriceData handles a simple product with customer group pricing enabled and on sale
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataSimpleWithCustomerGroupsAndSale(): void
    {
        $this->setupCurrencies();
        $this->setupSalePrice(true);
        $this->setupCustomerGroups();

        $this->registry->expects(self::exactly(4))
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct('simple', 17.00, true);
        $store = $this->setupStore();

        self::assertEquals(
            [
                'Prices' => ['17.00 GBP'],
                'SalePrices' => ['15.30 GBP'],
                'GroupPrices' => [
                    1 => [
                        'Prices' => ['18.00 GBP'],
                        'SalePrices' => ['16.20 GBP'],
                    ],
                    2 => [
                        'Prices' => ['19.00 GBP'],
                        'SalePrices' => ['17.10 GBP'],
                    ],
                    3 => [
                        'Prices' => ['20.00 GBP'],
                        'SalePrices' => ['18.00 GBP'],
                    ]
                ]
            ],
            $this->prices->getPriceData($store, $product, [])
        );
    }

    /**
     * Tests that getPriceData handles a simple product with customer group pricing enabled, on sale and multi-currency
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataSimpleWithCustomerGroupsSaleAndMultiCurrency(): void
    {
        $this->setupCurrencies('GBP', ['GBP', 'USD']);
        $this->setupSalePrice(true);
        $this->setupCustomerGroups();

        $this->registry->expects(self::exactly(4))
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct('simple', 17.00, true);
        $store = $this->setupStore('GBP', ['GBP', 'USD']);

        self::assertEquals(
            [
                'Prices' => ['17.00 GBP', '34.00 USD'],
                'SalePrices' => ['15.30 GBP', '30.60 USD'],
                'GroupPrices' => [
                    1 => [
                        'Prices' => ['18.00 GBP', '36.00 USD'],
                        'SalePrices' => ['16.20 GBP', '32.40 USD'],
                    ],
                    2 => [
                        'Prices' => ['19.00 GBP', '38.00 USD'],
                        'SalePrices' => ['17.10 GBP', '34.20 USD'],
                    ],
                    3 => [
                        'Prices' => ['20.00 GBP', '40.00 USD'],
                        'SalePrices' => ['18.00 GBP', '36.00 USD'],
                    ]
                ]
            ],
            $this->prices->getPriceData($store, $product, [])
        );
    }

    /**
     * Tests that getPriceData handles a bundle product
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataBundle(): void
    {
        $this->setupCurrencies();
        $this->setupSalePrice();
        $this->setupBundlePricing();

        $this->registry->expects(self::once())
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct(BundleType::TYPE_CODE, 17.00);
        $store = $this->setupStore();

        self::assertEquals(
            [
                'Prices' => ['14.00 GBP', '20.00 GBP'],
                'SalePrices' => [],
                'GroupPrices' => []
            ],
            $this->prices->getPriceData($store, $product, [])
        );
    }

    /**
     * Tests that getPriceData handles a bundle product when on sale
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataBundleOnSale(): void
    {
        $this->setupCurrencies();
        $this->setupSalePrice();
        $this->setupBundlePricing(true);

        $this->registry->expects(self::once())
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct(BundleType::TYPE_CODE, 17.00);
        $store = $this->setupStore();

        self::assertEquals(
            [
                'Prices' => ['14.00 GBP', '20.00 GBP'],
                'SalePrices' => ['12.60 GBP', '18.00 GBP'],
                'GroupPrices' => []
            ],
            $this->prices->getPriceData($store, $product, [])
        );
    }

    /**
     * Tests that getPriceData handles a configurable product
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testGetPriceDataConfigurable(): void
    {
        $this->setupCurrencies();
        $this->setupSalePrice();
        $children = $this->setupConfigurableChildren();

        $this->registry->expects(self::once())
            ->method('unregister')
            ->with(Prices::REGISTRY_KEY_CUSTOMER_GROUP);

        $product = $this->setupProduct(Configurable::TYPE_CODE, 17.00);
        $store = $this->setupStore();

        self::assertEquals(
            [
                'Prices' => ['16.00 GBP', '19.00 GBP'],
                'SalePrices' => [],
                'GroupPrices' => []
            ],
            $this->prices->getPriceData($store, $product, $children)
        );
    }
}
