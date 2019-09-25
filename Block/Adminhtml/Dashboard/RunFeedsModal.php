<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;

/**
 * Class RunFeedsModal
 *
 * Block for Feeds Modal popup
 */
class RunFeedsModal extends Template
{
    /** @var Stores $storesViewModel */
    private $storesViewModel;

    public function __construct(
        Context $context,
        Stores $storesViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storesViewModel = $storesViewModel;
    }

    /**
     * @return Stores
     */
    public function getPureclarityStoresViewModel()
    {
        return $this->storesViewModel;
    }
}
