<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\State;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;

/**
 * Class Dashboard
 *
 * Block for Dashboard page content
 */
class Dashboard extends Template
{
    /** @var State $stateViewModel */
    private $stateViewModel;

    /** @var Stores $storesViewModel */
    private $storesViewModel;

    /**
     * @param Context $context
     * @param State $stateViewModel
     * @param Stores $storesViewModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        State $stateViewModel,
        Stores $storesViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->stateViewModel  = $stateViewModel;
        $this->storesViewModel = $storesViewModel;
    }

    /**
     * @return State
     */
    public function getPureclarityStateViewModel()
    {
        return $this->stateViewModel;
    }

    /**
     * @return Stores
     */
    public function getPureclarityStoresViewModel()
    {
        return $this->storesViewModel;
    }
}
