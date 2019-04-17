<?php

namespace Pureclarity\Core\CustomerData;

class Customer implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    protected $customerSession;
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerSession = $customerSession;
        $this->logger = $logger;
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
