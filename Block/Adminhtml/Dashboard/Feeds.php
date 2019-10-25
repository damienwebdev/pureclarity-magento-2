<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\FeedStatus;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;

/**
 * Class Feeds
 *
 * Block for Feeds content on dashboard page
 */
class Feeds extends Template
{
    /** @var FeedStatus $feedStatusViewModel */
    private $feedStatusViewModel;

    /** @var Stores $storesViewModel */
    private $storesViewModel;

    /**
     * @param Context $context
     * @param FeedStatus $feedStatusViewModel
     * @param Stores $storesViewModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        FeedStatus $feedStatusViewModel,
        Stores $storesViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->feedStatusViewModel = $feedStatusViewModel;
        $this->storesViewModel     = $storesViewModel;
    }

    /**
     * @return FeedStatus
     */
    public function getPureclarityFeedStatusViewModel()
    {
        return $this->feedStatusViewModel;
    }

    /**
     * @return Stores
     */
    public function getPureclarityStoresViewModel()
    {
        return $this->storesViewModel;
    }
}
