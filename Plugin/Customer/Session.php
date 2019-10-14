<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Plugin\Customer;

use Magento\Framework\Registry;
use Magento\Customer\Model\Session as CustomerSession;
use Pureclarity\Core\Model\ProductExport\PriceHandler;

/**
 * Class Session
 *
 * Overrides customer group when building product pricing in feeds
 */
class Session
{
    /** @var Registry $registry */
    private $registry;
    
    /**
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }
    
    /**
     * Checks to see if we've got something in the registry to override the customer group ID
     * being returned to this function.
     *
     * Needed because bundle product pricing doesnt get the right customer group id
     * as it always looks in the session, even on command line, so when doing feed exports
     * and with customer group pricing enabled, we get the wrong prices
     *
     * @param CustomerSession $subject
     * @param int $customerGroupId
     * @return mixed
     */
    public function afterGetCustomerGroupId(
        CustomerSession $subject,
        $customerGroupId
    ) {
        
        $customerGroupIdOverride = $this->registry->registry(PriceHandler::REGISTRY_KEY_CUSTOMER_GROUP);
        if ($customerGroupIdOverride !== null) {
            return $customerGroupIdOverride;
        }
        
        return $customerGroupId;
    }
}
