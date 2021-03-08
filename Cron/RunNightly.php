<?php
declare(strict_types=1);

/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Cron;

use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Cron;
use Psr\Log\LoggerInterface;

/**
 * Class RunNightly
 *
 * Controls the execution of nightly feeds to be sent to PureClarity.
 */
class RunNightly
{
    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Cron $feedRunner */
    private $feedRunner;

    /**
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     * @param Cron $feedRunner
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        CoreConfig $coreConfig,
        Cron $feedRunner
    ) {
        $this->storeManager = $storeManager;
        $this->logger       = $logger;
        $this->coreConfig   = $coreConfig;
        $this->feedRunner   = $feedRunner;
    }

    /**
     * Runs feeds for every enabled store view, nightly.
     * called via cron at 3am (see /etc/crontab.xml)
     */
    public function execute()
    {
        foreach ($this->storeManager->getStores() as $store) {
            // Only generate feeds when feed notification is active
            if ($this->coreConfig->isDailyFeedActive($store->getId())) {
                $this->logger->debug('PureClarity: Nightly Feeds being run for Store View ' . $store->getId());
                $this->feedRunner->allFeeds($store->getId());
            }
        }
    }
}
