<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\ResourceModel\State;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pureclarity\Core\Model\State as StateModel;
use Pureclarity\Core\Model\ResourceModel\State as StateResource;

/**
 * Class Collection
 *
 * Collection resource model for pureclarity_state table
 */
class Collection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init(
            StateModel::class,
            StateResource::class
        );
    }
}
