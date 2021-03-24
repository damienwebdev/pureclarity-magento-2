<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Api;

interface FeedDataManagementInterface
{
    /**
     * Returns the page size for the feed.
     *
     * @return int
     */
    public function getPageSize(): int;

    /**
     * Returns the total number of pages in this feed
     * @param int $storeId
     * @return int
     */
    public function getTotalPages(int $storeId): int;

    /**
     * Returns a page of feed data
     *
     * @param int $storeId
     * @param int $pageNum
     * @return mixed[]
     */
    public function getPageData(int $storeId, int $pageNum): array;
}
