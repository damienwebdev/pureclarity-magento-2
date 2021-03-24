<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Category;

use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Api\CategoryFeedRowDataManagementInterface;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class RowData
 *
 * Handles individual category data rows in the feed
 */
class RowData implements CategoryFeedRowDataManagementInterface
{
    /** @var string */
    private $placeholderUrl;

    /** @var string */
    private $secondaryPlaceholderUrl;

    /** @var StoreInterface */
    private $currentStore;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CoreConfig */
    private $coreConfig;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CoreConfig $coreConfig
    ) {
        $this->storeManager = $storeManager;
        $this->coreConfig   = $coreConfig;
    }

    /**
     * Builds the customer data for the category feed.
     * @param int $storeId
     * @param Category $category
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     */
    public function getRowData(int $storeId, $category): array
    {
        // Build data
        $categoryData = [
            'Id' => $category->getId(),
            'DisplayName' => $category->getName(),
            'Image' => $this->getImageUrl($storeId, $category),
            'Description' => $category->getData('description') ?: '',
            'Link' => '/',
            'ParentIds' => []
        ];

        // Set URL and Parent ID
        if ($category->getLevel() > 1) {
            $categoryData['Link'] = $this->removeUrlProtocol($category->getUrl() ?: '');
            $categoryData['ParentIds'] = [$category->getParentCategory()->getId()];
        }

        // Check whether to ignore this category in recommenders
        if ($category->getData('pureclarity_hide_from_feed') === '1') {
            $categoryData['ExcludeFromRecommenders'] = true;
        }

        //Check if category is active
        if (!$category->getIsActive()) {
            $categoryData['IsActive'] = false;
        }

        $overrideImageUrl = $this->getOverrideImageUrl($storeId, $category);
        if ($overrideImageUrl !== '') {
            $categoryData['OverrideImage'] = $overrideImageUrl;
        }

        return $categoryData;
    }

    /**
     * Get category image URL
     * @param int $storeId
     * @param Category $category
     * @return string
     * @throws LocalizedException
     */
    public function getImageUrl(int $storeId, $category): string
    {
        $categoryImage = $category->getImageUrl() ?: '';
        if ($categoryImage !== '') {
            $categoryImageUrl = $categoryImage;
        } else {
            $categoryImageUrl = $this->getCategoryPlaceholderUrl($storeId);
        }

        return $this->removeUrlProtocol($categoryImageUrl);
    }

    /**
     * Gets the image override URL for the category
     * @param int $storeId
     * @param Category $category
     * @return string
     * @throws NoSuchEntityException
     */
    public function getOverrideImageUrl(int $storeId, $category): string
    {
        // Get override image
        $overrideImage = $category->getData('pureclarity_category_image') ?: '';
        if ($overrideImage !== '') {
            $overrideImageUrl = sprintf(
                '%scatalog/pureclarity_category_image/%s',
                $this->getCurrentStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
                $overrideImage
            );
        } else {
            $overrideImageUrl = $this->getSecondaryCategoryPlaceholderUrl($storeId);
        }

        return $this->removeUrlProtocol($overrideImageUrl);
    }

    /**
     * Removes protocol from the start of $url
     * @param $url string
     * @return string
     */
    public function removeUrlProtocol(string $url): string
    {
        return str_replace(['https:', 'http:'], '', $url);
    }

    /**
     * Gets the placeholder url from the config
     * @param int $storeId
     * @return string
     */
    private function getCategoryPlaceholderUrl(int $storeId): string
    {
        if ($this->placeholderUrl === null) {
            $this->placeholderUrl = $this->coreConfig->getCategoryPlaceholderUrl($storeId) ?: '';
        }
        return $this->placeholderUrl;
    }

    /**
     * Gets the secondary placeholder url from the config
     * @param int $storeId
     * @return string
     */
    private function getSecondaryCategoryPlaceholderUrl(int $storeId): string
    {
        if ($this->secondaryPlaceholderUrl === null) {
            $this->secondaryPlaceholderUrl = $this->coreConfig->getSecondaryCategoryPlaceholderUrl($storeId) ?: '';
        }
        return $this->secondaryPlaceholderUrl;
    }

    /**
     * Gets a Store object for the given Store
     * @param int $storeId
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getCurrentStore(int $storeId): StoreInterface
    {
        if ($this->currentStore === null) {
            $this->currentStore = $this->storeManager->getStore($storeId);
        }
        return $this->currentStore;
    }
}
