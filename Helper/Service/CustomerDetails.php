<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper\Service;

use Magento\Customer\Model\Session;

class CustomerDetails
{
    /** @var \Magento\Customer\Model\Session */
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
    * Returns customer data if we need to add a login event to the initialiazaiton process
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
                'prefix' => $customer->getPrefix()
            ];
            $this->customerSession->setPureclarityTriggerCustomerDetails(false);
        }
        
        return $customerData;
    }
    
    /**
     * Returns customer data if we need to add a login event to the initialiazaiton process
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
