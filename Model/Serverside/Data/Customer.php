<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Serverside\Data;

use Magento\Customer\Model\Session as CustomerSession;

/**
 * Serverside Customer handler, gets customer info from session and determines if an event needs to be fired
 */
class Customer
{
    /** @var CustomerSession */
    private $customerSession;

    /**
     * @param CustomerSession $customerSession
     */
    public function __construct(
        CustomerSession $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * Checks to see if the customer has changed, to determine if an event gets fired
     *
     * @return array
     */
    public function checkCustomer()
    {
        $lastDetails = $this->customerSession->getPureclarityLastCustomerDetailsHash();
        $details = $this->getCustomerDetails();
        $send = false;

        if ($details['hash'] !== $lastDetails) {
            $this->customerSession->setPureclarityLastCustomerDetailsHash($details['hash']);
            $send = true;
        }

        return [
            'send' => $send,
            'details' => $details['details']
        ];
    }

    /**
     * Gets the customer details from the session
     *
     * @return array
     */
    public function getCustomerDetails()
    {
        $hash = '';
        $details = [];
        $customer = $this->customerSession->getCustomer();
        if ($customer && $customer->getId()) {
            $details = [
                'userid' => $customer->getId(),
                'email' => $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'groupid' => $customer->getGroupId()
            ];

            if ($customer->getDob()) {
                $data['customer']['dob'] = $customer->getDob();
            }

            $hash = implode('|', $details);
        }

        return [
            'hash' => $hash,
            'details' => $details
        ];
    }
}
