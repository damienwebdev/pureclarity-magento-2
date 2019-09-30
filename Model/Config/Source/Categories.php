<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Config\Source;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Categories
 *
 * Category dropdown used in config dropdowns
 */
class Categories implements OptionSourceInterface
{
    /** @var array[] $categories */
    private $categories = [
        [
            "label" => "  ",
            "value" => "-1"
        ]
    ];

    /** @var CategoryRepository $categoryRepository */
    private $categoryRepository;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /**
     * @param CategoryRepository $categoryRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
    }

    public function buildCategories()
    {
        $rootCategories = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    if (!in_array($store->getRootCategoryId(), $rootCategories)) {
                        $rootCategories[] = $store->getRootCategoryId();
                        $this->getSubGategories($store->getRootCategoryId());
                    }
                }
            }
        }
    }

    private function getSubGategories($id, $prefix = '')
    {

        $category = $this->categoryRepository->get($id);
        $label = $prefix . $category->getName();
        $this->categories[] = [
            "value" => $category->getId(),
            "label" => $label
        ];
        $subcategories = $category->getChildrenCategories();
        foreach ($subcategories as $subcategory) {
            $this->getSubGategories($subcategory->getId(), $label . ' -> ');
        }
    }

    public function toOptionArray()
    {
        $this->buildCategories();
        return $this->categories;
    }
}
