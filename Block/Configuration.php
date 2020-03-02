<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Pureclarity\Core\ViewModel\Configuration as ConfigurationViewModel;

/**
 * Class Configuration
 *
 * configuration Javascript output
 */
class Configuration extends Template
{
    /** @var ConfigurationViewModel $configuration */
    private $configuration;

    /**
     * @param Context $context
     * @param ConfigurationViewModel $configuration
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigurationViewModel $configuration,
        array $data = []
    ) {
        $this->configuration = $configuration;
        parent::__construct($context, $data);
    }

    /**
     * Converts the configuration to a json encoded string
     *
     * @return ConfigurationViewModel
     */
    public function getPureclarityConfigurationViewModel()
    {
        return $this->configuration;
    }
}
