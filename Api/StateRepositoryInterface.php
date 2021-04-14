<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Pureclarity\Core\Api\Data\StateInterface;

interface StateRepositoryInterface
{
    /**
     * Gets all rows with the given name, one per store
     * @param string $name
     * @return StateInterface[]
     */
    public function getListByName(string $name) : array;

    /**
     * @param string $name
     * @param integer $storeId
     * @return StateInterface
     */
    public function getByNameAndStore($name, $storeId);

    /**
     * @param StateInterface $state
     * @return StateInterface
     * @throws CouldNotSaveException
     */
    public function save($state);

    /**
     * @param StateInterface $state
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete($state);
}
