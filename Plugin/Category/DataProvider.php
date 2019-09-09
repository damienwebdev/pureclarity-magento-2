<?php
namespace Pureclarity\Core\Plugin\Category;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;

class DataProvider
{
    
    private $coreHelper;
    private $storeManager;
    
    public function __construct(
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
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
