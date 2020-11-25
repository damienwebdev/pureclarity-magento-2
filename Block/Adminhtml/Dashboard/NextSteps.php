<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\Model\Dashboard;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;

/**
 * Class NextSteps
 *
 * Block for NextSteps Dashboard page content
 */
class NextSteps extends Template
{
    /** @var Dashboard $dashboard */
    private $dashboard;

    /** @var Stores $storesViewModel */
    private $storesViewModel;

    /**
     * @param Context $context
     * @param Dashboard $dashboard
     * @param Stores $storesViewModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        Dashboard $dashboard,
        Stores $storesViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dashboard       = $dashboard;
        $this->storesViewModel = $storesViewModel;
    }

    /**
     * @return Stores
     */
    public function getPureclarityStoresViewModel()
    {
        return $this->storesViewModel;
    }

    /**
     * Gets the next steps for display.
     *
     * @param integer $storeId
     * @return mixed[]
     */
    public function getNextSteps($storeId)
    {
        return $this->dashboard->getNextSteps($storeId);
    }

    /**
     * Turns the provided URL into a link to PureClarity admin.
     *
     * @param string $link
     * @return string
     */
    public function getAdminUrl($link)
    {
        return 'https://admin.pureclarity.com/' . $link;
    }
}
