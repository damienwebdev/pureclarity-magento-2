<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Pureclarity\Core\Model\Config\Source\Region;

/**
 * class Regions
 *
 * Regions ViewModel for Dashboard page
 */
class Regions implements ArgumentInterface
{
    /** @var $region Region */
    private $region;

    /**
     * @param Region $region
     */
    public function __construct(
        Region $region
    ) {
        $this->region = $region;
    }

    /**
     * Gets the PureClarity regions for display
     *
     * @return array[]
     */
    public function getPureClarityRegions()
    {
        return $this->region->toOptionArray();
    }
}
