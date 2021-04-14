<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers;

use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Brand
 *
 * Handles working out the brand ID for a given product
 */
class Brand
{
    /** @var string[] */
    private $brands;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var CategoryRepository */
    private $categoryRepository;

    /**
     * @param CoreConfig $coreConfig
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        CoreConfig $coreConfig,
        CategoryRepository $categoryRepository
    ) {
        $this->coreConfig         = $coreConfig;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Works out & returns the "brand" id for the given product
     *
     * @param int $storeId
     * @param Product|ProductInterface $product
     * @return int
     * @throws NoSuchEntityException
     */
    public function getBrandId(int $storeId, $product): int
    {
        $categoryIds = $product->getCategoryIds();
        $brands = $this->getBrands($storeId);
        $brandId = 0;
        foreach ($categoryIds as $id) {
            if (array_key_exists($id, $brands)) {
                $brandId = (int)$id;
            }
        }

        return $brandId;
    }

    /**
     * Loads all "brands" if brands are configured
     * @param int $storeId
     * @return string[]
     * @throws NoSuchEntityException
     */
    public function getBrands(int $storeId): array
    {
        if ($this->brands === null) {
            $this->brands = [];
            $brandCategoryId = $this->coreConfig->getBrandParentCategory($storeId);
            if ($brandCategoryId && $brandCategoryId !== "-1"
                && $this->coreConfig->isBrandFeedEnabled($storeId)
            ) {
                $category = $this->categoryRepository->get($brandCategoryId);
                $subcategories = $category->getChildrenCategories();
                foreach ($subcategories as $subcategory) {
                    $this->brands[$subcategory->getId()] = $subcategory->getName();
                }
            }
        }
    
        return $this->brands;
    }
}
