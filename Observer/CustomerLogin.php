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
use Pureclarity\Core\Model\CoreConfig;

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

    /** @var CoreConfig */
    private $coreConfig;

    /**
     * @param Session $customerSession
     * @param Data $coreHelper
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        Session $customerSession,
        Data $coreHelper,
        CoreConfig $coreConfig
    ) {
        $this->customerSession = $customerSession;
        $this->coreHelper      = $coreHelper;
        $this->coreConfig      = $coreConfig;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->coreConfig->isActive($this->coreHelper->getStoreId())) {
            $this->customerSession->setPureclarityTriggerCustomerDetails(true);
        }
    }
}
