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

class CustomerLogin implements ObserverInterface
{
    /** @var \Magento\Customer\Model\Session */
    private $customerSession;
    
    /** @var \Pureclarity\Core\Helper\Data */
    private $coreHelper;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Data $coreHelper
     */
    public function __construct(
        Session $customerSession,
        Data $coreHelper
    ) {
        $this->customerSession = $customerSession;
        $this->coreHelper      = $coreHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->coreHelper->isActive($this->coreHelper->getStoreId())) {
            $this->customerSession->setPureclarityTriggerCustomerDetails(true);
        }
    }
}
