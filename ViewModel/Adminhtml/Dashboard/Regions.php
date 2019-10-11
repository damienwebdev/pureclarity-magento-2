<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Pureclarity\Core\Model\Config\Source\Region;

/**
 * Class Regions
 *
 * Regions ViewModel for Dashboard page
 */
class Regions
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
