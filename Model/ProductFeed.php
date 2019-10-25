<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Magento\Framework\Model\AbstractModel;
use Pureclarity\Core\Model\ResourceModel\ProductFeed as ProductFeedResource;

/**
 * Class ProductFeed
 *
 * Data model for pureclarity_productfeed table
 */
class ProductFeed extends AbstractModel
{
    public function _construct()
    {
        $this->_init(ProductFeedResource::class);
    }
}
