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

    /** @var CoreConfig */
    private $coreConfig;

    /**
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        CoreConfig $coreConfig
    ) {
        $this->coreConfig   = $coreConfig;
    }

    /**
     * Builds the customer data for the category feed.
     * @param StoreInterface $store
     * @param Category $row
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRowData(StoreInterface $store, $row): array
    {
        // Build data
        $categoryData = [
            'Id' => $row->getId(),
            'DisplayName' => $row->getName(),
            'Image' => $this->getImageUrl($store, $row),
            'Description' => $row->getData('description') ?: '',
            'Link' => '/',
            'ParentIds' => []
        ];

        // Set URL and Parent ID
        if ($row->getLevel() > 1) {
            $categoryData['Link'] = $this->removeUrlProtocol($row->getUrl() ?: '');
            $categoryData['ParentIds'] = [$row->getParentCategory()->getId()];
        }

        // Check whether to ignore this category in recommenders
        if ($row->getData('pureclarity_hide_from_feed') === '1') {
            $categoryData['ExcludeFromRecommenders'] = true;
        }

        //Check if category is active
        if (!$row->getIsActive()) {
            $categoryData['IsActive'] = false;
        }

        $overrideImageUrl = $this->getOverrideImageUrl($store, $row);
        if ($overrideImageUrl !== '') {
            $categoryData['OverrideImage'] = $overrideImageUrl;
        }

        return $categoryData;
    }

    /**
     * Get category image URL
     * @param StoreInterface $store
     * @param Category $category
     * @return string
     * @throws LocalizedException
     */
    public function getImageUrl(StoreInterface $store, $category): string
    {
        $categoryImage = $category->getImageUrl() ?: '';
        if ($categoryImage !== '') {
            $categoryImageUrl = $categoryImage;
        } else {
            $categoryImageUrl = $this->getCategoryPlaceholderUrl($store);
        }

        return $this->removeUrlProtocol($categoryImageUrl);
    }

    /**
     * Gets the image override URL for the category
     * @param StoreInterface $store
     * @param Category $category
     * @return string
     * @throws NoSuchEntityException
     */
    public function getOverrideImageUrl(StoreInterface $store, $category): string
    {
        // Get override image
        $overrideImage = $category->getData('pureclarity_category_image') ?: '';
        if ($overrideImage !== '') {
            $overrideImageUrl = sprintf(
                '%scatalog/pureclarity_category_image/%s',
                $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
                $overrideImage
            );
        } else {
            $overrideImageUrl = $this->getSecondaryCategoryPlaceholderUrl($store);
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
     * @param StoreInterface $store
     * @return string
     */
    private function getCategoryPlaceholderUrl(StoreInterface $store): string
    {
        if ($this->placeholderUrl === null) {
            $this->placeholderUrl = $this->coreConfig->getCategoryPlaceholderUrl($store->getId()) ?: '';
        }
        return $this->placeholderUrl;
    }

    /**
     * Gets the secondary placeholder url from the config
     * @param StoreInterface $store
     * @return string
     */
    private function getSecondaryCategoryPlaceholderUrl(StoreInterface $store): string
    {
        if ($this->secondaryPlaceholderUrl === null) {
            $this->secondaryPlaceholderUrl = $this->coreConfig->getSecondaryCategoryPlaceholderUrl($store->getId()) ?: '';
        }
        return $this->secondaryPlaceholderUrl;
    }
}
