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
 * Class Stats
 *
 * Block for Stats Dashboard page content
 */
class Stats extends Template
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
     * Gets the stats for display.
     *
     * @param $storeId
     * @return mixed[]
     */
    public function getStats($storeId)
    {
        return $this->dashboard->getStats($storeId);
    }

    /**
     * Gets the stat title for display.
     *
     * @param string $type
     * @return string
     */
    public function getStatTitle($type)
    {
        $title = '';
        switch ($type) {
            case 'today':
                $title = 'Today';
                break;
            case 'last30days':
                $title = 'Last 30 days';
                break;
        }
        return $title;
    }

    /**
     * Gets the stat title for display.
     *
     * @param mixed $stat
     * @return boolean
     */
    public function hasRecTotalStats($stat)
    {
        return isset(
            $stat['RecommenderProductTotal'],
            $stat['RecommenderProductTotalDisplay'],
            $stat['OrderCount'],
            $stat['SalesTotalDisplay']
        ) && $stat['RecommenderProductTotal'] > 0;
    }

    /**
     * Gets the stat types to display.
     *
     * @return string[]
     */
    public function getStatKeysToShow()
    {
        return [
            'Impressions'                    => 'Impressions',
            'Sessions'                       => 'Sessions',
            'ConversionRate'                 => 'Conversion Rate',
            'SalesTotalDisplay'              => 'Sales Total',
            'OrderCount'                     => 'Orders',
            'RecommenderProductTotalDisplay' => 'Recommender Product Total',
        ];
    }
    /**
     * Formats display values if needed.
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    public function getStatDisplay($key, $value)
    {
        if ($key === 'ConversionRate') {
            $value .= '%';
        }
        return $value;
    }
}
