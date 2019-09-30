<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class ProductFeed
 *
 * Resource model for pureclarity_productfeed table
 */
class ProductFeed extends AbstractDb
{
    const TABLE = 'pureclarity_productfeed';

    protected function _construct()
    {
        $this->_init(
            self::TABLE,
            'id'
        );
    }
}
