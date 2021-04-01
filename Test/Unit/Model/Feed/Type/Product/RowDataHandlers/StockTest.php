<?php
declare(strict_types=1);

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Product\RowDataHandlers;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Stock;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Pureclarity\Core\Model\CoreConfig;
use ReflectionException;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;

/**
 * Class StockTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Stock
 * @see \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Stock
 */
class StockTest extends TestCase
{
    /** @var StockRegistryInterface | MockObject */
    private $stockRegistry;

    /** @var CoreConfig | MockObject */
    private $coreConfig;

    /** @var Stock */
    private $stock;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->stockRegistry = $this->createMock(StockRegistryInterface::class);
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->stock = new Stock(
            $this->stockRegistry,
            $this->coreConfig
        );
    }

    /**
     * Sets up a StockItemInterface mock
     * @param bool $inStock
     * @throws ReflectionException
     */
    public function setupStockItem(bool $inStock): void
    {
        $stockItem = $this->createMock(StockItemInterface::class);

        $stockItem->expects(self::once())
            ->method('getIsInStock')
            ->willReturn($inStock);

        $this->stockRegistry->expects(self::once())
            ->method('getStockItem')
            ->willReturn($stockItem);
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Stock::class, $this->stock);
    }

    /**
     * Tests that getStockFlag returns 'true' when product in stock
     * @throws ReflectionException
     */
    public function testGetStockFlagTrue(): void
    {
        $product = $this->createMock(Product::class);

        $product->method('getId')
            ->willReturn(1);

        $this->setupStockItem(true);

        self::assertEquals(
            'true',
            $this->stock->getStockFlag($product)
        );
    }

    /**
     * Tests that getStockFlag returns 'false' when product not in stock
     * @throws ReflectionException
     */
    public function testGetStockFlagFalse(): void
    {
        $product = $this->createMock(Product::class);

        $product->method('getId')
            ->willReturn(1);

        $this->setupStockItem(false);

        self::assertEquals(
            'false',
            $this->stock->getStockFlag($product)
        );
    }

    /**
     * Tests that isExcluded returns false when product in stock
     */
    public function testIsExcludedInStock(): void
    {
        $this->coreConfig->expects(self::never())
            ->method('getExcludeOutOfStockFromRecommenders');

        self::assertEquals(
            false,
            $this->stock->isExcluded(1, 'true')
        );
    }
    
    /**
     * Tests that isExcluded returns false when product not in stock, but exclusions disabled
     */
    public function testIsExcludedOutOfStockButExcludeDisabled(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('getExcludeOutOfStockFromRecommenders')
            ->willReturn(false);

        self::assertEquals(
            false,
            $this->stock->isExcluded(1, 'false')
        );
    }

    /**
     * Tests that isExcluded returns true when product not in stock, and exclusions enabled
     */
    public function testIsExcludedOutOfStockAndExcludeEnabled(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('getExcludeOutOfStockFromRecommenders')
            ->willReturn(true);

        self::assertEquals(
            true,
            $this->stock->isExcluded(1, 'false')
        );
    }
}
