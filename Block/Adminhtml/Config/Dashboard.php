<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml\Config;

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Dashboard extends Fieldset
{
    /**
     * Return header comment part of html for fieldset
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        $dashboardUrl = $this->getUrl('pureclarity/dashboard/index');

        $html = '<p>' . $this->escapeHtml(__('This page allows configuration of the PureClarity module')) . '</p>'
            . '<p>' . $this->escapeHtml(__('To run data feeds, install Zones or access documentation, please see the PureClarity Dashboard page')) . '</p>'
            . '<p><a href="' . $dashboardUrl . '">'
            . $this->escapeHtml(__('Go to PureClarity Dashboard'))
            . '</a></p>';

        return $html;
    }
}
