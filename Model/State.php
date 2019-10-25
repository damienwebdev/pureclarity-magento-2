<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Magento\Framework\Model\AbstractModel;
use Pureclarity\Core\Api\Data\StateInterface;
use Pureclarity\Core\Model\ResourceModel\State as StateResource;

/**
 * Class State
 *
 * Data model for pureclarity_state table
 */
class State extends AbstractModel implements StateInterface
{
    public function _construct()
    {
        $this->_init(StateResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        $this->setData(self::ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        return $this->getData(self::VALUE);
    }

    /**
     * @inheritdoc
     */
    public function setValue($value)
    {
        $this->setData(self::VALUE, $value);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($id)
    {
        $this->setData(self::STORE_ID, $id);
    }
}
