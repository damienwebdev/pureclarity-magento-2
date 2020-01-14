<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

// Note: using deprectaed class, otherwise Zone widgets are broken in 2.3.0
use Magento\Framework\Option\ArrayInterface;

/**
 * Class BmzDisplayMode
 *
 * BMZ display mode options
 */
class BmzDisplayMode implements ArrayInterface
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
