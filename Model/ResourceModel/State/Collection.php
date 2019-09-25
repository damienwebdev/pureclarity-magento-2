<?php

namespace Pureclarity\Core\Model\ResourceModel\State;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pureclarity\Core\Model\State as StateModel;
use Pureclarity\Core\Model\ResourceModel\State as StateResource;

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
