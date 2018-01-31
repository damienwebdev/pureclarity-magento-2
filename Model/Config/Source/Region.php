<?php

namespace Pureclarity\Core\Model\Config\Source;

class Region implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            [
                'label' => 'Region 1',
                'value' => 1
            ],
            [
                'label' => 'Region 2',
                'value' => 2
            ],
            [
                'label' => 'Region 3',
                'value' => 3
            ],
            [
                'label' => 'Region 4',
                'value' => 4
            ],
            [
                'label' => 'Region 5',
                'value' => 5
            ],
            [
                'label' => 'Region 6',
                'value' => 6
            ],
            [
                'label' => 'Region 7',
                'value' => 7
            ],
            [
                'label' => 'Region 8',
                'value' => 8
            ],
            [
                'label' => 'Region 9',
                'value' => 9
            ],
            [
                'label' => 'Region 10',
                'value' => 10
            ]
        ];
    }

}