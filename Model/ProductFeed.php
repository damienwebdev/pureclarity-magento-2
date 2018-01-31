<?php

namespace Pureclarity\Core\Model;

use Magento\Framework\Model\AbstractModel;

class ProductFeed extends AbstractModel
{
    public function _construct()
    {
        $this->_init('Pureclarity\Core\Model\ResourceModel\ProductFeed');
    }
}