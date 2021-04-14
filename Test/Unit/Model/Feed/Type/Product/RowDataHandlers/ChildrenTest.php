<?php
declare(strict_types=1);

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Product\RowDataHandlers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Children;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use \Magento\Bundle\Model\ResourceModel\Selection\Collection as BundleChildrenCollection;
use Magento\Bundle\Model\Product\Type;
use ReflectionException;

/**
 * Class ChildrenTest
 *
 * Tests methods in \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Children
 *
 * @see \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Children
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ChildrenTest extends TestCase
{
    /** @var CollectionFactory | MockObject */
    private $collectionFactory;

    /** @var ConfigurableFactory | MockObject */
    private $configurableFactory;

    /** @var Children */
    private $children;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->configurableFactory = $this->createMock(ConfigurableFactory::class);
        $this->children = new Children(
            $this->collectionFactory,
            $this->configurableFactory
        );
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Children::class, $this->children);
    }

    /**
     * Tests that loadChildData returns children for a configurable product.
     * @throws NoSuchEntityException|ReflectionException
     */
    public function testLoadChildDataWithConfigurableProduct(): void
    {
        $product = $this->createMock(Product::class);

        $product->method('getTypeId')
            ->willReturn(Configurable::TYPE_CODE);

        $product->method('getId')
            ->willReturn(1);

        $configurable = $this->createMock(Configurable::class);

        $configurable->method('getChildrenIds')
            ->willReturn([0 => [17,22]]);

        $this->configurableFactory->expects(self::once())
            ->method('create')
            ->willReturn($configurable);

        $collection = $this->createPartialMock(
            Collection::class,
            ['addAttributeToSelect', 'addFieldToFilter', 'getItems']
        );

        $collection->expects(self::once())
            ->method('addAttributeToSelect')
            ->with('*');

        $collection->expects(self::once())
            ->method('addFieldToFilter')
            ->with('entity_id', ['in' => [17,22]]);

        $collection->expects(self::once())
            ->method('getItems')
            ->willReturn([17,22]);

        $this->collectionFactory->expects(self::once())
            ->method('create')
            ->willReturn($collection);

        self::assertEquals(
            [17,22],
            $this->children->loadChildData($product)
        );
    }

    /**
     * Tests that loadChildData handles a configurable product with no children.
     * @throws ReflectionException
     */
    public function testLoadChildDataWithConfigurableProductWithException(): void
    {
        $product = $this->createMock(Product::class);

        $product->method('getTypeId')
            ->willReturn(Configurable::TYPE_CODE);

        $product->method('getId')
            ->willReturn(1);

        $configurable = $this->createMock(Configurable::class);

        $configurable->method('getChildrenIds')
            ->willReturn([0 => []]);

        $this->configurableFactory->expects(self::once())
            ->method('create')
            ->willReturn($configurable);

        $this->collectionFactory->expects(self::never())
            ->method('create');

        try {
            $this->children->loadChildData($product);
        } catch (NoSuchEntityException $e) {
            self::assertEquals('Cannot use configurable with no children', $e->getMessage());
        }
    }

    /**
     * Tests that loadChildData returns children for a grouped product.
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testLoadChildDataWithGroupedProduct(): void
    {
        $product = $this->createMock(Product::class);
        $typeInstance = $this->createMock(AbstractType::class);
        $typeInstance->expects(self::once())
            ->method('getAssociatedProducts')
            ->with($product)
            ->willReturn([17,22]);

        $product->method('getTypeId')
            ->willReturn(Grouped::TYPE_CODE);

        $product->method('getTypeInstance')
            ->willReturn($typeInstance);

        self::assertEquals(
            [17,22],
            $this->children->loadChildData($product)
        );
    }

    /**
     * Tests that loadChildData returns children for a bundle product.
     * @throws ReflectionException|NoSuchEntityException
     */
    public function testLoadChildDataWithBundleProduct(): void
    {
        $product = $this->createMock(Product::class);

        $product->method('getTypeId')
            ->willReturn(Type::TYPE_CODE);

        $collection = $this->createPartialMock(
            BundleChildrenCollection::class,
            ['getItems']
        );

        $collection->expects(self::once())
            ->method('getItems')
            ->willReturn([17,22]);

        $typeInstance = $this->getMockForAbstractClass(
            AbstractType::class,
            [],
            '',
            false,
            true,
            true,
            ['getOptionsIds', 'getSelectionsCollection', 'getTypeInstance']
        );

        $typeInstance->expects(self::once())
            ->method('getOptionsIds')
            ->with($product)
            ->willReturn([2,3]);

        $typeInstance->expects(self::once())
            ->method('getSelectionsCollection')
            ->with([2,3], $product)
            ->willReturn($collection);

        $product->method('getTypeInstance')
            ->willReturn($typeInstance);

        self::assertEquals(
            [17,22],
            $this->children->loadChildData($product)
        );
    }
}
