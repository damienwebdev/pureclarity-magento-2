<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Api;

interface FeedRowDataManagementInterface
{
    /**
     * Returns a formatted row of data for this feed
     * @param mixed $row
     * @return array
     */
    public function getRowData($row): array;
}
