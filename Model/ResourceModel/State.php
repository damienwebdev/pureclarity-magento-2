<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Pureclarity\Core\Api\Data\StateInterface;

class State extends AbstractDb
{
    const TABLE = 'pureclarity_state';

    protected function _construct()
    {
        $this->_init(self::TABLE, StateInterface::ID);
    }
}
