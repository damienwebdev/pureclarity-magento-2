<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Api;

use Magento\Store\Api\Data\StoreInterface;

interface FeedRowDataManagementInterface
{
    /**
     * Returns a formatted row of data for this feed
     * @param StoreInterface $store
     * @param mixed $row
     * @return array
     */
    public function getRowData(StoreInterface $store, $row): array;
}
