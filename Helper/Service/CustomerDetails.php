<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper\Service;

use Magento\Customer\Model\Session;

/**
 * Class CustomerDetails
 *
 * Helper class for customer details related actions
 */
class CustomerDetails
{
    /** @var Session */
    private $customerSession;

    /**
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

   /**
    * Returns customer data if we need to add a login event to the initialization process
    *
    * @return mixed[]
    */
    public function getCustomerDetails()
    {
        $customerData = $this->getEmptyCustomerDetails();
        
        if ($this->customerSession->isLoggedIn() && $this->customerSession->getPureclarityTriggerCustomerDetails()) {
            $customer = $this->customerSession->getCustomer();
            $customerData['trigger'] = true;
            $customerData['customer'] = [
                'userid' => $customer->getId(),
                'email' => $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'prefix' => $customer->getPrefix(),
                'groupid' => $customer->getGroupId()
            ];
            $this->customerSession->setPureclarityTriggerCustomerDetails(false);
        }
        
        return $customerData;
    }
    
    /**
     * Returns empty customer data if we need to add a login event to the initialization process
     *
     * @return mixed[]
     */
    public function getEmptyCustomerDetails()
    {
        $customerData = [
            'trigger' => false
        ];
        
        return $customerData;
    }
}
