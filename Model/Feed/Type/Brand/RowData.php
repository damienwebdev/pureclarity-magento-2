<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Brand;

use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
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
    private $secondaryUrl;

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
     * Builds the customer data for the brand feed.
     * @param StoreInterface $store
     * @param Category $row
     * @return array
     * @throws LocalizedException
     */
    public function getRowData(StoreInterface $store, $row): array
    {
        $brandData = [
            'Id' => $row->getId(),
            'DisplayName' =>  $row->getName(),
            'Description' => $row->getData('description') ?: ''
        ];

        // Get brand image
        $brandImageUrl = $row->getImageUrl() ?: $this->getCategoryPlaceholderUrl($store);
        $brandData['Image'] = $this->removeUrlProtocol($brandImageUrl);

        // Get override image
        $overrideImageUrl = null;
        $overrideImage = $row->getData('pureclarity_category_image') ?: '';
        if ($overrideImage !== '') {
            $overrideImageUrl = sprintf(
                '%scatalog/pureclarity_category_image/%s',
                $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
                $overrideImage
            );
        } else {
            $overrideImageUrl = $this->getSecondaryCategoryPlaceholderUrl($store);
        }
        $overrideImageUrl = $this->removeUrlProtocol($overrideImageUrl);

        if ($overrideImageUrl) {
            $brandData['OverrideImage'] = $overrideImageUrl;
        }

        $url = $row->getUrl() ?: '';
        $brandData['Link'] = $this->removeUrlProtocol($url);

        // Check whether to ignore this brand in recommenders
        if ($row->getData('pureclarity_hide_from_feed') === '1') {
            $brandData['ExcludeFromRecommenders'] = true;
        }

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
        if ($this->secondaryUrl === null) {
            $this->secondaryUrl = $this->coreConfig->getSecondaryCategoryPlaceholderUrl($store->getId()) ?: '';
        }
        return $this->secondaryUrl;
    }
}
