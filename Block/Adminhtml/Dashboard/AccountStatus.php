<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\Model\Dashboard;

/**
 * Class AccountStatus
 *
 * Block for Account status Dashboard page content
 */
class AccountStatus extends Template
{
    /** @var Dashboard $dashboard */
    private $dashboard;

    /**
     * @param Context $context
     * @param Dashboard $dashboard
     * @param array $data
     */
    public function __construct(
        Context $context,
        Dashboard $dashboard,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dashboard = $dashboard;
        ;
    }

    /**
     * Gets the account status for display.
     *
     * @return mixed[]
     */
    public function getAccountStatus()
    {
        // TODO: sort out multistore code here
        return $this->dashboard->getAccountStatus(0);
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
