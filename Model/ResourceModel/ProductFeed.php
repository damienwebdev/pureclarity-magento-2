<?php

namespace Pureclarity\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductFeed extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(
            'pureclarity_productfeed', 
            'id');
    }
}