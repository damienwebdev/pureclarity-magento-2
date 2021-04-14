<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\User;

use Magento\Store\Api\Data\StoreInterface;
use Pureclarity\Core\Api\UserFeedRowDataManagementInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Customer\Model\Customer;

/**
 * Class RowData
 *
 * Handles individual customer data rows in the feed
 */
class RowData implements UserFeedRowDataManagementInterface
{
    /** @var array */
    private $customerGroups;

    /** @var CustomerGroupCollectionFactory */
    private $collectionFactory;

    /**
     * @param CustomerGroupCollectionFactory $collectionFactory
     */
    public function __construct(
        CustomerGroupCollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Builds the customer data for the user feed.
     * @param StoreInterface $store
     * @param Customer $row
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getRowData(StoreInterface $store, $row): array
    {
        $customerGroups = $this->getCustomerGroups();
        $data = [
            'UserId' => $row->getId(),
            'Email' => $row->getEmail(),
            'FirstName' => $row->getFirstname(),
            'LastName' => $row->getLastname()
        ];

        $prefix = $row->getPrefix();
        if ($prefix) {
            $data['Salutation'] = $prefix;
        }

        $dob = $row->getDob();
        if ($dob) {
            $data['DOB'] = $dob;
        }

        $groupId = $row->getGroupId();
        if ($groupId && isset($customerGroups[$groupId])) {
            $data['Group'] = $customerGroups[$groupId]['label'];
            $data['GroupId'] = $groupId;
        }

        $gender = $row->getGender();
        switch ($gender) {
            case 1: // Male
                $data['Gender'] = 'M';
                break;
            case 2: // Female
                $data['Gender'] = 'F';
                break;
        }

        $data['City'] = $row->getData('city');
        $data['State'] = $row->getData('region');
        $data['Country'] = $row->getData('country_id');
        return $data;
    }

    /**
     * Loads all customer groups in the system
     * @return array
     */
    public function getCustomerGroups(): array
    {
        if ($this->customerGroups === null) {
            $customerGroupCollection = $this->collectionFactory->create();
            $this->customerGroups = $customerGroupCollection->toOptionArray();
        }

        return $this->customerGroups;
    }
}
