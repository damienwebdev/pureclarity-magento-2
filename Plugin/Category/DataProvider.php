<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Plugin\Category;

use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Helper\Data;

/**
 * Class DataProvider
 *
 * Adds extra PureClarity info to category data provider
 */
class DataProvider
{
    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /**
     * @param Data $coreHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $coreHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->coreHelper = $coreHelper;
        $this->storeManager = $storeManager;
    }
    
    public function afterGetData(CategoryDataProvider $subject, array $data)
    {
        
        $category = $subject->getCurrentCategory();
        if (!$category) {
            return $data;
        }

        $image = $category->getData("pureclarity_category_image");
        if (!$image) {
            return $data;
        }

        $imageName = $image;
        if (!is_string($image)) {
            if (is_array($image)) {
                $imageName = $image[0]['name'];
            }
        }

        $categoryImageUrl = $this->coreHelper->getAdminImageUrl(
            $this->storeManager->getStore(),
            $imageName,
            "pureclarity_category_image"
        );

        $imagePath = $this->coreHelper->getAdminImagePath(
            $this->storeManager->getStore(),
            $imageName,
            "pureclarity_category_image"
        );
        $fileSize = 0;
        if ($imagePath) {
            $fileSize = filesize($imagePath);
        }
        
        $seoImageData = [
            0 => [
                'name' => $imageName,
                'url' => $categoryImageUrl,
                'size' => $fileSize
            ],
        ];
        $data[$category->getId()]["pureclarity_category_image"] = $seoImageData;
    
        return $data;
    }
}
