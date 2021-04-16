<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Api;

use Magento\Store\Api\Data\StoreInterface;

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
     * @param StoreInterface $store
     * @return int
     */
    public function getTotalPages(StoreInterface $store): int;

    /**
     * Returns a page of feed data
     *
     * @param StoreInterface $store
     * @param int $pageNum
     * @return mixed[]
     */
    public function getPageData(StoreInterface $store, int $pageNum): array;
}
