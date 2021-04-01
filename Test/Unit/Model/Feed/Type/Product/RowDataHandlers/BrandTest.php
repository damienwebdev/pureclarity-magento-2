<?php
declare(strict_types=1);

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Product\RowDataHandlers;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Brand;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Model\CategoryRepository;
use ReflectionException;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\Product;

/**
 * Class BrandTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Brand
 * @see \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Brand
 */
class BrandTest extends TestCase
{
    /** @var int */
    private const STORE_ID = 1;

    /** @var CoreConfig | MockObject */
    private $coreConfig;

    /** @var CategoryRepository | MockObject */
    private $categoryRepository;

    /** @var Brand */
    private $brand;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->brand = new Brand(
            $this->coreConfig,
            $this->categoryRepository
        );
    }

    /**
     * Sets up a CategoryInterface mock
     * @param int $numChildren
     * @return CategoryInterface|MockObject
     * @throws ReflectionException
     */
    public function setupCategory(int $numChildren)
    {
        $category = $this->getMockForAbstractClass(
            CategoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getChildrenCategories']
        );

        $children = [];
        for ($i = 1; $i <= $numChildren; $i++) {
            $child = $this->createMock(CategoryInterface::class);

            $child->method('getId')
                ->willReturn($i);

            $child->method('getName')
                ->willReturn('Child ' . $i);

            $children[$i] = $child;
        }

        $category->method('getChildrenCategories')
            ->willReturn($children);

        return $category;
    }

    /**
     * Sets up a brand CategoryInterface array
     *
     * @param string $brandCategoryId
     * @param bool $brandsEnabled
     * @param int $numBrands
     * @throws ReflectionException
     */
    public function setupBrands(string $brandCategoryId, bool $brandsEnabled, int $numBrands): void
    {
        $this->coreConfig->expects(self::once())
            ->method('getBrandParentCategory')
            ->with(self::STORE_ID)
            ->willReturn($brandCategoryId);

        if ((int)$brandCategoryId > 0) {
            $this->coreConfig->expects(self::once())
                ->method('isBrandFeedEnabled')
                ->with(self::STORE_ID)
                ->willReturn($brandsEnabled);
        }

        if ($brandsEnabled && $brandCategoryId > 0) {
            $this->categoryRepository->expects(self::once())
                ->method('get')
                ->with($brandCategoryId)
                ->willReturn($this->setupCategory($numBrands));
        } else {
            $this->categoryRepository->expects(self::never())
                ->method('get');
        }
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Brand::class, $this->brand);
    }

    /**
     * Tests that the brand id is selected correctly from the products category ids (when configured)
     * @throws NoSuchEntityException|ReflectionException
     */
    public function testGetBrandIdWithMatchingBrand(): void
    {
        $this->setupBrands('1', true, 2);

        $product = $this->createMock(Product::class);

        $product->method('getCategoryIds')
            ->willReturn([2,3,5]);

        $brand = $this->brand->getBrandId(self::STORE_ID, $product);

        self::assertEquals(2, $brand);
    }

    /**
     * Tests that the brand id is not selected from the products category ids when it doesnt match any ids
     * @throws NoSuchEntityException|ReflectionException
     */
    public function testGetBrandIdNoMatchingBrand(): void
    {
        $this->setupBrands('1', true, 2);

        $product = $this->createMock(Product::class);

        $product->method('getCategoryIds')
            ->willReturn([6,3,5]);

        $brand = $this->brand->getBrandId(self::STORE_ID, $product);

        self::assertEquals(0, $brand);
    }

    /**
     * Tests that the brand id is not selected when there are no brands
     * @throws NoSuchEntityException|ReflectionException
     */
    public function testGetBrandIdNoBrands(): void
    {
        $this->setupBrands('1', false, 2);

        $product = $this->createMock(Product::class);

        $product->method('getCategoryIds')
            ->willReturn([6,3,5]);

        $brand = $this->brand->getBrandId(self::STORE_ID, $product);

        self::assertEquals(0, $brand);
    }

    /**
     * Tests that the brands are returned correctly by getBrands when configured
     * @throws NoSuchEntityException|ReflectionException
     */
    public function testGetBrandsWithBrands(): void
    {
        $this->setupBrands('1', true, 2);

        $brands = $this->brand->getBrands(self::STORE_ID);

        self::assertEquals(
            [
                1 => 'Child 1',
                2 => 'Child 2',
            ],
            $brands
        );
    }

    /**
     * Tests that no brands are returned by getBrands when no brands present
     * @throws NoSuchEntityException|ReflectionException
     */
    public function testGetBrandsWithNoBrands(): void
    {
        $this->setupBrands('1', true, 0);

        $brands = $this->brand->getBrands(self::STORE_ID);

        self::assertEquals([], $brands);
    }

    /**
     * Tests that no brands are returned by getBrands when no brand feed disabled
     * @throws NoSuchEntityException|ReflectionException
     */
    public function testGetBrandsWhenDisabled(): void
    {
        $this->setupBrands('1', false, 0);

        $brands = $this->brand->getBrands(self::STORE_ID);

        self::assertEquals([], $brands);
    }

    /**
     * Tests that no brands are returned by getBrands when no brand parent selected
     * @throws NoSuchEntityException|ReflectionException
     */
    public function testGetBrandsWhenNoSelectedBrand(): void
    {
        $this->setupBrands('', true, 0);

        $brands = $this->brand->getBrands(self::STORE_ID);

        self::assertEquals([], $brands);
    }

    /**
     * Tests that no brands are returned by getBrands when no valid brand parent selected
     * @throws NoSuchEntityException|ReflectionException
     */
    public function testGetBrandsWhenBadSelectedBrand(): void
    {
        $this->setupBrands('-1', true, 0);

        $brands = $this->brand->getBrands(self::STORE_ID);

        self::assertEquals([], $brands);
    }
}
