<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Log;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Class Delete
 *
 * Deletes log files
 */
class Delete
{
    /** @var DirectoryList $directoryList */
    private $directoryList;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var File */
    private $file;

    /**
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param File $file
     */
    public function __construct(
        DirectoryList $directoryList,
        LoggerInterface $logger,
        File $file
    ) {
        $this->directoryList = $directoryList;
        $this->logger        = $logger;
        $this->file          = $file;
    }

    /**
     * Deletes the PureClarity log file
     * @return bool
     */
    public function deleteLogs(): bool
    {
        $success = false;

        try {
            $logPath = $this->directoryList->getPath(DirectoryList::VAR_DIR)
                . DIRECTORY_SEPARATOR . 'log'  . DIRECTORY_SEPARATOR;
            $this->deleteFile($logPath . 'pureclarity.log');
            $success = true;
        } catch (FileSystemException $e) {
            $this->logger->error('PureClarity error deleting log file: ' . $e->getMessage());
        }

        return $success;
    }

    /**
     * Deletes the file with the given filename
     * @param string $fileName
     * @throws FileSystemException
     */
    public function deleteFile(string $fileName): void
    {
        if ($this->file->isExists($fileName)) {
            $this->file->deleteFile($fileName);
        }
    }
}
