<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Logs;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class Config
 *
 * Dashboard Logs Config ViewModel
 */
class Config implements ArgumentInterface
{
    /** @var bool $debugLogging */
    private $debugLogging;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /**
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        CoreConfig $coreConfig
    ) {
        $this->coreConfig = $coreConfig;
    }

    /**
     * Returns whether debug logging is enabled or not
     *
     * @return boolean
     */
    public function isLoggingEnabled(): bool
    {
        if ($this->debugLogging === null) {
            $this->debugLogging = $this->coreConfig->isDebugLoggingEnabled();
        }

        return $this->debugLogging;
    }
}
