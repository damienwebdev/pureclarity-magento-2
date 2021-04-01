<?php
declare(strict_types=1);

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Product\RowDataHandlers;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Categories;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use ReflectionException;

/**
 * Class CategoriesTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Categories
 * @see \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Categories
 */
class CategoriesTest extends TestCase
{
    /** @var Collection | MockObject */
    private $categoryCollection;

    /** @var CollectionFactory | MockObject */
    private $collectionFactory;

    /** @var Categories */
    private $categories;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->categoryCollection = $this->createPartialMock(
            CollectionFactory::class,
            ['addAttributeToSelect','addFieldToFilter','getItems']
        );
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->collectionFactory->method('create')
            ->willReturn($this->categoryCollection);

        $this->categories = new Categories(
            $this->collectionFactory
        );
    }

    /**
     * Sets up a CategoryInterface mock
     *
     * @param int $categoryId
     * @return CategoryInterface|MockObject
     * @throws ReflectionException
     */
    public function setupCategory(int $categoryId)
    {
        $category = $this->getMockForAbstractClass(
            CategoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getName']
        );

        $category->method('getName')
            ->willReturn('Category ' . $categoryId);

        return $category;
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Categories::class, $this->categories);
    }

    /**
     * Tests that getCategoryData returns the loaded category ids & names
     * @throws LocalizedException|ReflectionException
     */
    public function testGetCategoryData(): void
    {
        $product = $this->createMock(Product::class);

        $product->method('getCategoryIds')
            ->willReturn([2,3]);

        $this->categoryCollection->method('addAttributeToSelect')
            ->with('name');

        $this->categoryCollection->method('addFieldToFilter')
            ->with('is_active', ['in' => ['1']]);

        $this->categoryCollection->method('getItems')
            ->with()
            ->willReturn([
                1 => $this->setupCategory(1),
                2 => $this->setupCategory(2),
                3 => $this->setupCategory(3)
            ]);

        $categories = $this->categories->getCategoryData($product);

        self::assertEquals([2,3], $categories['Categories']);
        self::assertEquals(['Category 2', 'Category 3'], $categories['MagentoCategories']);
    }
}
