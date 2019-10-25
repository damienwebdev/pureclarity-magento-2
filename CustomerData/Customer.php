<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session;

/**
 * Class Customer
 *
 * Data model for PureClarity customer-details event (see frontend/section.xml for usages)
 */
class Customer implements SectionSourceInterface
{
    /** @var Session $customerSession */
    private $customerSession;

    /**
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }
    
    public function getSectionData()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            if ($customer) {
                $data = [
                    "isLoggedIn" => true,
                    "customer"=> [
                        'userid' => $customer->getId(),
                        'email' => $customer->getEmail(),
                        'firstname' => $customer->getFirstname(),
                        'lastname' => $customer->getLastname(),
                        'groupid' => $customer->getGroupId()
                    ]
                ];
                if ($customer->getDob()) {
                    $data['customer']['dob'] = $customer->getDob();
                }
                return $data;
            }
        }
        return [
                "isLoggedIn" => false
        ];
    }
}
