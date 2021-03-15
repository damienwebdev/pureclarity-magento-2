<?php
declare(strict_types=1);

/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Cron;

use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Cron;
use Pureclarity\Core\Model\Feed\Request;

/**
 * Class RunScheduled
 *
 * Controls the execution of scheduled feeds to be sent to PureClarity.
 */
class RunRequestedFeeds
{
    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Cron $feedRunner */
    private $feedRunner;

    /** @var Request $feedRequest */
    private $feedRequest;

    /**
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     * @param Cron $feedRunner
     * @param Request $feedRequest
     */
    
    public function __construct(
        LoggerInterface $logger,
        CoreConfig $coreConfig,
        Cron $feedRunner,
        Request $feedRequest
    ) {
        $this->logger      = $logger;
        $this->coreConfig  = $coreConfig;
        $this->feedRunner  = $feedRunner;
        $this->feedRequest = $feedRequest;
    }

    /**
     * Runs feeds that have been requested by a button press in admin
     * called via cron every minute (see /etc/crontab.xml)
     */
    public function execute()
    {
        $requests = $this->feedRequest->getAllRequestedFeeds();

        foreach ($requests as $storeId => $feeds) {
            if ($this->coreConfig->isActive($storeId)) {
                $this->logger->debug('PureClarity: Requested Feeds being run for Store View ' . $storeId);
                $this->feedRequest->deleteRequestedFeeds($storeId);
                $this->feedRunner->selectedFeeds($storeId, $feeds);
            }
        }
    }
}
