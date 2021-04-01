<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Api;

use PureClarity\Api\Feed\Feed;

interface FeedManagementInterface
{
    /**
     * Returns whether this feed is enabled
     *
     * @param int $storeId
     * @return bool
     */
    public function isEnabled(int $storeId): bool;

    /**
     * Gets the feed builder class for this type of feed
     *
     * @param string $accessKey
     * @param string $secretKey
     * @param string $region
     *
     * @return Feed
     */
    public function getFeedBuilder(string $accessKey, string $secretKey, string $region): Feed;

    /**
     * Gets the feed data handler for this feed
     * @return FeedDataManagementInterface
     */
    public function getFeedDataHandler(): FeedDataManagementInterface;

    /**
     * Gets the feed row data handler for this feed
     * @return FeedRowDataManagementInterface
     */
    public function getRowDataHandler(): FeedRowDataManagementInterface;

    /**
     * Returns whether this feed requires app emulation
     */
    public function requiresEmulation(): bool;
}
