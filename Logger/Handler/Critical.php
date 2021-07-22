<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger as MonologLogger;

/**
 * Class Critical
 *
 * Logs critical messages to a PureClarity log file
 */
class Critical extends BaseHandler
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = MonologLogger::CRITICAL;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/pureclarity/critical.log';
}
