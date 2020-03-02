<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Mode
 *
 * contains list of valid modes for use in config
 */
class Mode implements OptionSourceInterface
{
    /** @var string Client Side Mode - Default */
    const MODE_CLIENTSIDE = 'clientside';

    /** @var string Server Side Mode */
    const MODE_SERVERSIDE = 'serverside';

    /**
     * Default PureClarity valid mode labels
     *
     * @var string[]
     */
    private $validModes = [
        self::MODE_CLIENTSIDE => 'Client-side',
        self::MODE_SERVERSIDE => 'Serverside'
    ];

    /**
     * Gets array of valid modes for use in a dropdown
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        $regions = [];
        foreach ($this->validModes as $value => $label) {
            $regions[] = [
                'label' => $label,
                'value' => $value
            ];
        }

        return $regions;
    }
}
