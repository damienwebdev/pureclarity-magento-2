<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Pureclarity\Core\Helper\Data;

/**
 * Class CustomerLogin
 *
 * Customer login observer, stores session details for login tracking
 */
class CustomerLogin implements ObserverInterface
{
    /** @var Session */
    private $customerSession;
    
    /** @var Data */
    private $coreHelper;

    /**
     * @param Session $customerSession
     * @param Data $coreHelper
     */
    public function __construct(
        Session $customerSession,
        Data $coreHelper
    ) {
        $this->customerSession = $customerSession;
        $this->coreHelper      = $coreHelper;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->coreHelper->isActive($this->coreHelper->getStoreId())) {
            $this->customerSession->setPureclarityTriggerCustomerDetails(true);
        }
    }
}
