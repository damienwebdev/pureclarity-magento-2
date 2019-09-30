<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Region
 *
 * contains list of valid regions for use in config & signup validation
 */
class Region implements OptionSourceInterface
{
    /**
     * Default list of valid regions to use with PureClarity
     *
     * @var string[]
     */
    private $validRegions = [
        1 => 'Europe',
        4 => 'USA'
    ];

    /**
     * Gets array of valid regions
     *
     * @return array[]
     */
    public function getValidRegions()
    {
        $regions = [];
        foreach ($this->validRegions as $value => $label) {
            $regions[] = [
                'label' => $label,
                'value' => $value
            ];
        }

        return $regions;
    }

    /**
     * Gets array of valid regions for use in a dropdown
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        $regions = [];
        foreach ($this->validRegions as $value => $label) {
            $regions[] = [
                'label' => $label,
                'value' => $value
            ];
        }

        return $regions;
    }
}
