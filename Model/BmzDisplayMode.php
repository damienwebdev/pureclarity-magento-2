<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;
 
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class BmzDisplayMode
 *
 * BMZ display mode options
 */
class BmzDisplayMode implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'default', 'label' => __('Default')],
            ['value' => 'mobile', 'label' => __('Mobile Only')],
            ['value' => 'desktop', 'label' => __('Desktop Only')]
        ];
    }
}
