<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type;

use PureClarity\Api\Feed\Feed;
use PureClarity\Api\Feed\Type\BrandFactory;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;
use Pureclarity\Core\Api\BrandFeedDataManagementInterface;
use Pureclarity\Core\Api\BrandFeedRowDataManagementInterface;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class Brand
 *
 * Handles running of brand feed
 */
class Brand implements FeedManagementInterface
{
    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var BrandFactory */
    private $brandFeedFactory;

    /** @var BrandFeedDataManagementInterface */
    private $feedDataHandler;

    /** @var BrandFeedRowDataManagementInterface */
    private $rowDataHandler;

    /**
     * @param CoreConfig $coreConfig
     * @param BrandFactory $brandFeedFactory
     * @param BrandFeedDataManagementInterface $feedDataHandler
     * @param BrandFeedRowDataManagementInterface $rowDataHandler
     */
    public function __construct(
        CoreConfig $coreConfig,
        BrandFactory $brandFeedFactory,
        BrandFeedDataManagementInterface $feedDataHandler,
        BrandFeedRowDataManagementInterface $rowDataHandler
    ) {
        $this->coreConfig       = $coreConfig;
        $this->brandFeedFactory = $brandFeedFactory;
        $this->feedDataHandler  = $feedDataHandler;
        $this->rowDataHandler   = $rowDataHandler;
    }

    /**
     * Returns whether this feed is enabled
     *
     * @param int $storeId
     * @return bool
     */
    public function isEnabled(int $storeId): bool
    {
        $enabled = $this->coreConfig->isBrandFeedEnabled($storeId);
        $brandCategoryId = $this->coreConfig->getBrandParentCategory($storeId);
        return $enabled && $brandCategoryId && $brandCategoryId !== '-1';
    }

    /**
     * Gets the feed builder class from the PureClarity API
     *
     * @param string $accessKey
     * @param string $secretKey
     * @param string $region
     * @return Feed
     */
    public function getFeedBuilder(string $accessKey, string $secretKey, string $region): Feed
    {
        return $this->brandFeedFactory->create([
            'accessKey' => $accessKey,
            'secretKey' => $secretKey,
            'region' => $region
        ]);
    }

    /**
     * Gets the brand feed data handler
     *
     * @return BrandFeedDataManagementInterface
     */
    public function getFeedDataHandler(): FeedDataManagementInterface
    {
        return $this->feedDataHandler;
    }

    /**
     * Gets the brand feed row data handler
     *
     * @return BrandFeedRowDataManagementInterface
     */
    public function getRowDataHandler(): FeedRowDataManagementInterface
    {
        return $this->rowDataHandler;
    }
}
