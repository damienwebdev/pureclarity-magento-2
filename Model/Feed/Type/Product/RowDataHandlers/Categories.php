<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Categories
 *
 * Builds category data for the product feed, for the given product
 */
class Categories
{
    /** @var Category[] */
    private $categories;

    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Gets a list of ID & names of categories for the given product.
     *
     * @param Product|ProductInterface $product
     * @return array
     * @throws LocalizedException
     */
    public function getCategoryData($product): array
    {
        $categoryIds = $product->getCategoryIds();
        $categories = $this->getCategories();

        // Get a list of the category names
        $categoryList = [];
        foreach ($categoryIds as $id) {
            if (isset($categories[$id])) {
                $categoryList[] = $categories[$id]->getName();
            }
        }

        return [
            'Categories' => $categoryIds,
            'MagentoCategories' => array_values(array_unique($categoryList, SORT_STRING))
        ];
    }

    /**
     * Loads all active categories
     *
     * @return Category[]
     * @throws LocalizedException
     */
    public function getCategories(): array
    {
        if ($this->categories === null) {
            $this->categories = [];
            $categoryCollection = $this->collectionFactory->create();
            $categoryCollection->addAttributeToSelect('name');
            $categoryCollection->addFieldToFilter('is_active', ['in' => ['1']]);
            $this->categories = $categoryCollection->getItems();
        }

        return $this->categories;
    }
}
