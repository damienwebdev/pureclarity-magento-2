<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Cron;

use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Delta\Runner;

/**
 * Class RunDeltas
 *
 * Controls the execution of deltas sent to PureClarity.
 */
class RunDeltas
{
    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var Runner $deltaRunner */
    private $deltaRunner;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CoreConfig $coreConfig
     * @param Runner $deltaRunner
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CoreConfig $coreConfig,
        Runner $deltaRunner
    ) {
        $this->storeManager = $storeManager;
        $this->coreConfig   = $coreConfig;
        $this->deltaRunner  = $deltaRunner;
    }

    /**
     * Runs deltas for every enabled store view.
     * called via cron every minute (see /etc/crontab.xml)
     */
    public function execute(): void
    {
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->coreConfig->areDeltasEnabled((int)$store->getId())) {
                $this->deltaRunner->runDeltas((int)$store->getId());
            }
        }
    }
}
