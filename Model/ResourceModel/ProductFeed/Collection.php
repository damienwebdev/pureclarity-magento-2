<?php

namespace Pureclarity\Core\Model\ResourceModel\ProductFeed;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init(
            'Pureclarity\Core\Model\ProductFeed',
            'Pureclarity\Core\Model\ResourceModel\ProductFeed'
        );
    }
}
