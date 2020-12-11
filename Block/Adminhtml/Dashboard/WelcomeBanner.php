<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Welcome;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;

/**
 * Class WelcomeBanner
 *
 * Block for Welcome Banner Dashboard page content
 */
class WelcomeBanner extends Template
{
    /** @var Welcome $welcomeViewModel */
    private $welcomeViewModel;

    /** @var Stores $storesViewModel */
    private $storesViewModel;

    /**
     * @param Context $context
     * @param Welcome $welcomeViewModel
     * @param Stores $storesViewModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        Welcome $welcomeViewModel,
        Stores $storesViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->welcomeViewModel = $welcomeViewModel;
        $this->storesViewModel = $storesViewModel;
    }

    /**
     * @return Welcome
     */
    public function getPureclarityWelcomeViewModel()
    {
        return $this->welcomeViewModel;
    }

    /**
     * @return Stores
     */
    public function getPureclarityStoresViewModel()
    {
        return $this->storesViewModel;
    }
}
