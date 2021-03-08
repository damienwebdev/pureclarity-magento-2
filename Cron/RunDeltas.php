<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Cron;

use Pureclarity\Core\Model\Cron;

/**
 * Class RunDeltas
 *
 * Controls the execution of deltas sent to PureClarity.
 */
class RunDeltas
{
    /** @var Cron $feedRunner */
    private $feedRunner;

    /**
     * @param Cron $feedRunner
     */
    public function __construct(
        Cron $feedRunner
    ) {
        $this->feedRunner = $feedRunner;
    }

    /**
     * Runs deltas for every enabled store view.
     * called via cron every minute (see /etc/crontab.xml)
     */
    public function execute()
    {
        $this->feedRunner->reindexData();
    }
}
