<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface StateSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return void
     */
    public function getItems();

    /**
     * @param StateInterface[] $items
     * @return StateSearchResultInterface
     */
    public function setItems(array $items);
}
