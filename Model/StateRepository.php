<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Model\ResourceModel\State\Collection;
use Pureclarity\Core\Model\ResourceModel\StateFactory;
use Pureclarity\Core\Model\ResourceModel\State\CollectionFactory;
use Pureclarity\Core\Api\Data\StateInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * Class StateRepository
 *
 * Repository class for getting rows out of pureclarity_state table
 */
class StateRepository implements StateRepositoryInterface
{
    /** @var CollectionFactory $collectionFactory */
    private $collectionFactory;

    /** @var StateFactory $stateFactory */
    private $stateFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param StateFactory $stateFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StateFactory $stateFactory
    ) {
        $this->collectionFactory         = $collectionFactory;
        $this->stateFactory              = $stateFactory;
    }

    /**
     * @inheritdoc
     */
    public function getByNameAndStore($name, $storeId)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $collection->addFieldToSelect(StateInterface::ID);
        $collection->addFieldToSelect(StateInterface::NAME);
        $collection->addFieldToSelect(StateInterface::VALUE);
        $collection->addFieldToFilter(StateInterface::NAME, $name);
        $collection->addFieldToFilter(StateInterface::STORE_ID, $storeId);

        $collection->setPageSize(1);
        $collection->load();

        return $collection->getFirstItem();
    }

    /**
     * @inheritdoc
     */
    public function save($state)
    {
        try {
            $resource = $this->stateFactory->create();
            $resource->save($state);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the State: %1',
                $exception->getMessage()
            ));
        }

        return $state;
    }

    /**
     * @inheritdoc
     */
    public function delete($state)
    {
        try {
            $resource = $this->stateFactory->create();
            $resource->delete($state);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Unable to remove State %1',
                $exception->getMessage()
            ));
        }

        return true;
    }
}
