<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type;

use PureClarity\Api\Feed\Feed;
use PureClarity\Api\Feed\Type\ProductFactory;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;
use Pureclarity\Core\Api\ProductFeedDataManagementInterface;
use Pureclarity\Core\Api\ProductFeedRowDataManagementInterface;

/**
 * Class Product
 *
 * Handles running of Product feed
 */
class Product implements FeedManagementInterface
{
    /** @var ProductFactory */
    private $productFeedFactory;

    /** @var ProductFeedDataManagementInterface */
    private $feedDataHandler;

    /** @var ProductFeedRowDataManagementInterface */
    private $rowDataHandler;

    /**
     * @param ProductFactory $productFeedFactory
     * @param ProductFeedDataManagementInterface $feedDataHandler
     * @param ProductFeedRowDataManagementInterface $rowDataHandler
     */
    public function __construct(
        ProductFactory $productFeedFactory,
        ProductFeedDataManagementInterface $feedDataHandler,
        ProductFeedRowDataManagementInterface $rowDataHandler
    ) {
        $this->productFeedFactory = $productFeedFactory;
        $this->feedDataHandler    = $feedDataHandler;
        $this->rowDataHandler     = $rowDataHandler;
    }

    /**
     * Returns whether this feed is enabled
     *
     * @param int $storeId
     * @return bool
     */
    public function isEnabled(int $storeId): bool
    {
        return true;
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
        return $this->productFeedFactory->create([
            'accessKey' => $accessKey,
            'secretKey' => $secretKey,
            'region' => $region
        ]);
    }

    /**
     * Gets the Product feed data handler
     *
     * @return ProductFeedDataManagementInterface
     */
    public function getFeedDataHandler(): FeedDataManagementInterface
    {
        return $this->feedDataHandler;
    }

    /**
     * Gets the Product feed row data handler
     *
     * @return ProductFeedRowDataManagementInterface
     */
    public function getRowDataHandler(): FeedRowDataManagementInterface
    {
        return $this->rowDataHandler;
    }

    /**
     * Returns whether this feed requires emulation
     * @return bool
     */
    public function requiresEmulation(): bool
    {
        return true;
    }
}
