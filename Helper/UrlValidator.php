<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper;

/**
 * Class Url validates URL and checks that it has allowed scheme
 * Duplicate of Magento\Framework\Validator\Url , for 2.1 compatibility
 */
class UrlValidator
{
    /**
     * Validate URL and check that it has allowed scheme
     *
     * @param string $value
     * @param array $allowedSchemes
     * @return bool
     */
    public function isValid($value, array $allowedSchemes = [])
    {
        $isValid = true;

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $isValid = false;
        }

        if ($isValid && !empty($allowedSchemes)) {
            $url = parse_url($value);
            if (empty($url['scheme']) || !in_array($url['scheme'], $allowedSchemes)) {
                $isValid = false;
            }
        }

        return $isValid;
    }
}
