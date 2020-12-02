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
 * Class AccountStatus
 *
 * Block for Account status Dashboard page content
 */
class AccountStatus extends Template
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
     * Gets the account status for display.
     *
     * @param integer $storeId
     * @return mixed[]
     */
    public function getAccountStatus($storeId)
    {
        return $this->dashboard->getAccountStatus($storeId);
    }

    /**
     * Gets the class to use for status box.
     *
     * @param int $daysLeft
     * @return string
     */
    public function getStatusClass($daysLeft)
    {
        $class = '';

        if ($daysLeft <= 4 && $daysLeft > 1) {
            $class = 'pc-ft-warning';
        } elseif ($daysLeft <= 1) {
            $class = 'pc-ft-error';
        }

        return $class;
    }

    /**
     * Gets the end date for the free trial.
     *
     * @param int $daysLeft
     * @return string
     */
    public function getEndDate($daysLeft)
    {
        try {
            $date = $this->_localeDate->date();
            $date->modify('+' . $daysLeft . ' days');
            $formatted = $this->formatDate($date, 1, false);
        } catch (\Exception $e) {
            $formatted = '';
        }

        return $formatted;
    }
}
