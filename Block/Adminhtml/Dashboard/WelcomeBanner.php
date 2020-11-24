<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Welcome;

/**
 * Class WelcomeBanner
 *
 * Block for Welcome Banner Dashboard page content
 */
class WelcomeBanner extends Template
{
    /** @var Welcome $welcomeViewModel */
    private $welcomeViewModel;

    /**
     * @param Context $context
     * @param Welcome $welcomeViewModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        Welcome $welcomeViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->welcomeViewModel = $welcomeViewModel;
    }

    /**
     * @return Welcome
     */
    public function getPureclarityWelcomeViewModel()
    {
        return $this->welcomeViewModel;
    }
}
