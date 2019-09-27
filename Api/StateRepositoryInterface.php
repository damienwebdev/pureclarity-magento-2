<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Pureclarity\Core\Api\Data\StateInterface;
use Pureclarity\Core\Api\Data\StateSearchResultInterface;

interface StateRepositoryInterface
{
    /**
     * @param string $name
     * @param integer $storeId
     * @return StateInterface
     */
    public function getByNameAndStore($name, $storeId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return StateSearchResultInterface
     */
    public function getList($searchCriteria);

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
