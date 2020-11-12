<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\Model\Dashboard;

/**
 * Class NextSteps
 *
 * Block for NextSteps Dashboard page content
 */
class NextSteps extends Template
{
    /** @var Dashboard $dashboard */
    private $dashboard;

    /**
     * @param Context $context
     * @param Dashboard $dashboard
     * @param array $data
     */
    public function __construct(
        Context $context,
        Dashboard $dashboard,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dashboard = $dashboard;
    }

    /**
     * Gets the next steps for display.
     *
     * @return mixed[]
     */
    public function getNextSteps()
    {
        // TODO: sort out multistore code here
        return $this->dashboard->getNextSteps(0);
    }

    /**
     * Turns the provided URL into a link to PureClarity admin.
     *
     * @param string $link
     * @return string
     */
    public function getAdminUrl($link)
    {
        return 'https://admin.pureclarity.com/' . $link;
    }
}
