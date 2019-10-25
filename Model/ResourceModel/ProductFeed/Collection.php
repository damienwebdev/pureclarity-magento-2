<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\ResourceModel\ProductFeed;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pureclarity\Core\Model\ProductFeed;
use \Pureclarity\Core\Model\ResourceModel\ProductFeed as ProductFeedResource;

/**
 * Class Collection
 *
 * Collection resource model for pureclarity_productfeed table
 */
class Collection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init(
            ProductFeed::class,
            ProductFeedResource::class
        );
    }
}
