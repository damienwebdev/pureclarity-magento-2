<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\User;

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
    private $customerGroupCollectionFactory;

    /**
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory
     */
    public function __construct(
        CustomerGroupCollectionFactory $customerGroupCollectionFactory
    ) {
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
    }

    /**
     * Builds the customer data for the user feed.
     * @param int $storeId
     * @param Customer $customer
     * @return array
     */
    public function getRowData(int $storeId, $customer): array
    {
        $customerGroups = $this->getCustomerGroups();
        $data = [
            'UserId' => $customer->getId(),
            'Email' => $customer->getEmail(),
            'FirstName' => $customer->getFirstname(),
            'LastName' => $customer->getLastname()
        ];

        $prefix = $customer->getPrefix();
        if ($prefix) {
            $data['Salutation'] = $prefix;
        }

        $dob = $customer->getDob();
        if ($dob) {
            $data['DOB'] = $dob;
        }

        $groupId = $customer->getGroupId();
        if ($groupId && isset($customerGroups[$groupId])) {
            $data['Group'] = $customerGroups[$groupId]['label'];
            $data['GroupId'] = $groupId;
        }

        $gender = $customer->getGender();
        switch ($gender) {
            case 1: // Male
                $data['Gender'] = 'M';
                break;
            case 2: // Female
                $data['Gender'] = 'F';
                break;
        }

        $data['City'] = $customer->getData('city');
        $data['State'] = $customer->getData('region');
        $data['Country'] = $customer->getData('country_id');
        return $data;
    }

    /**
     * Loads all customer groups in the system
     * @return array
     */
    public function getCustomerGroups(): array
    {
        if ($this->customerGroups === null) {
            $customerGroupCollection = $this->customerGroupCollectionFactory->create();
            $this->customerGroups = $customerGroupCollection->toOptionArray();
        }

        return $this->customerGroups;
    }
}
