<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Checkout\Onepage;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Pureclarity\Core\ViewModel\Configuration;
use Pureclarity\Core\ViewModel\Checkout\Onepage\Success as SuccessViewModel;

class Success extends Template
{
    /** @var SuccessViewModel */
    private $successViewModel;

    /** @var Configuration */
    private $configuration;

    /**
     * @param Context $context
     * @param SuccessViewModel $successViewModel
     * @param Configuration $configuration
     * @param array $data
     */
    public function __construct(
        Context $context,
        SuccessViewModel $successViewModel,
        Configuration $configuration,
        array $data = []
    ) {
        $this->successViewModel = $successViewModel;
        $this->configuration    = $configuration;
        parent::__construct($context, $data);
    }

    /**
     * @return SuccessViewModel
     */
    public function getPureclarityOrderSuccessViewModel()
    {
        return $this->successViewModel;
    }

    /**
     * @return Configuration
     */
    public function getPureclarityConfigurationViewModel()
    {
        return $this->configuration;
    }
}
