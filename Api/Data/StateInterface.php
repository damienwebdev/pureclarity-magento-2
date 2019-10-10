<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Api\Data;

interface StateInterface
{
    const ID       = 'state_id';
    const NAME     = 'name';
    const VALUE    = 'value';
    const STORE_ID = 'store_id';

    /**
     * @return integer|null
     */
    public function getId();

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id);

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     * @return void
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getValue();

    /**
     * @param string $value
     * @return void
     */
    public function setValue($value);

    /**
     * @return integer|null
     */
    public function getStoreId();

    /**
     * @param integer $id
     * @return void
     */
    public function setStoreId($id);
}
