<?php
namespace Pureclarity\Core\Model;
 
class BmzDisplayMode implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'default', 'label' => __('Default')],
            ['value' => 'mobile', 'label' => __('Mobile Only')],
            ['value' => 'desktop', 'label' => __('Desktop Only')]
        ];
    }
}
