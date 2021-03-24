<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Brand;

use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Api\BrandFeedRowDataManagementInterface;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class RowData
 *
 * Handles individual brand data rows in the feed
 */
class RowData implements BrandFeedRowDataManagementInterface
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
     * Builds the customer data for the brand feed.
     * @param int $storeId
     * @param Category $brand
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     */
    public function getRowData(int $storeId, $brand): array
    {
        $brandData = [
            'Id' => $brand->getId(),
            'DisplayName' =>  $brand->getName(),
            'Description' => $brand->getData('description') ?: ''
        ];

        // Get brand image
        $brandImageUrl = $brand->getImageUrl() ?: $this->getCategoryPlaceholderUrl($storeId);
        $brandData['Image'] = $this->removeUrlProtocol($brandImageUrl);

        // Get override image
        $overrideImageUrl = null;
        $overrideImage = $brand->getData('pureclarity_category_image') ?: '';
        if ($overrideImage !== '') {
            $overrideImageUrl = sprintf(
                '%scatalog/pureclarity_category_image/%s',
                $this->getCurrentStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
                $overrideImage
            );
        } else {
            $overrideImageUrl = $this->getSecondaryCategoryPlaceholderUrl($storeId);
        }
        $overrideImageUrl = $this->removeUrlProtocol($overrideImageUrl);

        if ($overrideImageUrl) {
            $brandData['OverrideImage'] = $overrideImageUrl;
        }

        $url = $brand->getUrl() ?: '';
        $brandData['Link'] = $this->removeUrlProtocol($url);

        // Check whether to ignore this brand in recommenders
        if ($brand->getData('pureclarity_hide_from_feed') === '1') {
            $brandData['ExcludeFromRecommenders'] = true;
        }
        var_dump($brandData);

        return $brandData;
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
