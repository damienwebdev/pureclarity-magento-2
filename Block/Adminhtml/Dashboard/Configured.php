<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Feeds;

/**
 * Class Signup
 *
 * Block for Signup content on dashboard page
 */
class Configured extends Template
{
    /** @var Feeds $feedsViewModel */
    private $feedsViewModel;

    /**
     * @param Context $context
     * @param Feeds $feedsViewModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        Feeds $feedsViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->feedsViewModel  = $feedsViewModel;
    }

    /**
     * @return Feeds
     */
    public function getPureclarityFeedsViewModel()
    {
        return $this->feedsViewModel;
    }
}
