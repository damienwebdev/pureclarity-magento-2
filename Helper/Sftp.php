<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper;

use Magento\Framework\Filesystem\Io\Sftp as FilesystemIoSftp;
use Psr\Log\LoggerInterface;

/**
 * Class Sftp
 *
 * Handles SFTP interactions for feeds
 */
class Sftp
{
    /** @var LoggerInterface $logger */
    private $logger;

    /** @var FilesystemIoSftp $sftp */
    private $sftp;

    /**
     * @param LoggerInterface $logger
     * @param FilesystemIoSftp $sftp
     */
    public function __construct(
        LoggerInterface $logger,
        FilesystemIoSftp $sftp
    ) {
        $this->logger = $logger;
        $this->sftp   = $sftp;
    }

    public function send($host, $port, $username, $password, $filename, $payload, $directory = null)
    {
        $path = '/' . ($directory?$directory.'/':'') . $filename;
        $success = true;
        try {
            $this->sftp->open(["host"=>($host.":".$port),"username"=>$username, "password"=>$password]);
            $this->sftp->write($path, $payload);
        } catch (\Exception $e) {
            $this->logger->error("ERROR: Processing PureClarity SFTP transfer. See Exception log for details.");
            $this->logger->critical($e);
            $success = false;
        }
        $this->sftp->close();
        return $success;
    }
}
