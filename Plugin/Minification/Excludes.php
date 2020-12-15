<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Plugin\Minification;

use Magento\Framework\View\Asset\Minification;

/**
 * Class Excludes
 *
 * Adds PureClarity external Javscript files to minification excludes
 */
class Excludes
{
    /**
     * Adds PureClarity external Javscript files to minification excludes
     * otherwise they won't load with minification enabled.
     * @param Minification $subject
     * @param array $result
     * @param string $contentType
     * @return array
     */
    public function afterGetExcludes(Minification $subject, $result, $contentType)
    {
        if ($contentType === 'js') {
            $result[] = 'socket\.io';
            $result[] = 'jssor\.slider\.mini';
        }
        return $result;
    }
}
