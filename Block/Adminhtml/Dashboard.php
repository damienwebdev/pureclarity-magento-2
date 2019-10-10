<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\State;

/**
 * Class Dashboard
 *
 * Block for Dashboard page content
 */
class Dashboard extends Template
{
    /** @var State $stateViewModel */
    private $stateViewModel;

    /**
     * @param Context $context
     * @param State $stateViewModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        State $stateViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->stateViewModel = $stateViewModel;
    }

    /**
     * @return State
     */
    public function getPureclarityStateViewModel()
    {
        return $this->stateViewModel;
    }
}
