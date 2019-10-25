<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml;

use Magento\Framework\View\Design\Theme\LabelFactory;

/**
 * Class Themes
 *
 * Themes ViewModel for Dashboard page
 */
class Themes
{
    /** @var LabelFactory $labelFactory */
    private $labelFactory;

    /**
     * @param LabelFactory $labelFactory
     */
    public function __construct(
        LabelFactory $labelFactory
    ) {
        $this->labelFactory = $labelFactory;
    }

    /**
     * Gets list of stores for display
     * @return array
     */
    public function getThemes()
    {
        $label = $this->labelFactory->create();
        return $label->getLabelsCollection();
    }
}
