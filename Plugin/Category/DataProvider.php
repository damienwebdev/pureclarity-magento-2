<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Plugin\Category;

use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Helper\Data;
use Psr\Log\LoggerInterface;

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

    /** @var Filesystem $filesystem */
    private $filesystem;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param Data $coreHelper
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        Data $coreHelper,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        $this->coreHelper   = $coreHelper;
        $this->storeManager = $storeManager;
        $this->filesystem   = $filesystem;
        $this->logger       = $logger;
    }

    /**
     * Adds PureClarity override image data to the ui component data for category editing
     * @param CategoryDataProvider $subject
     * @param array $data
     * @return array
     */
    public function afterGetData(CategoryDataProvider $subject, array $data): array
    {
        try {
            $category = $subject->getCurrentCategory();
            if (!$category) {
                return $data;
            }

            $image = $category->getData("pureclarity_category_image");
            if (!$image) {
                return $data;
            }

            $imageName = $image;
            if (!is_string($image) && is_array($image)) {
                $imageName = $image[0]['name'];
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
                $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                $fileSize = $mediaDirectory->stat($imagePath)['size'];
            }

            $seoImageData = [
                0 => [
                    'name' => $imageName,
                    'url' => $categoryImageUrl,
                    'size' => $fileSize
                ],
            ];
            $data[$category->getId()]["pureclarity_category_image"] = $seoImageData ;

        } catch (NoSuchEntityException $e) {
            $this->logger->error('PureClarity category form data error: ' . $e->getMessage());
        }
        return $data;
    }
}
