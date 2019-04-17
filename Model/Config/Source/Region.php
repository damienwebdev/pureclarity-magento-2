<?php

namespace Pureclarity\Core\Model\Config\Source;

class Region implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            [
                'label' => 'Europe',
                'value' => 1
            ],
            [
                'label' => 'USA',
                'value' => 4
            ]
        ];
    }
}
