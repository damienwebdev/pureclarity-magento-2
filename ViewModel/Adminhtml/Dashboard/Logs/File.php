<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Logs;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class File
 *
 * Handles file information values for display on the logs page.
 */
class File implements ArgumentInterface
{
    /** @var string */
    private const LOG_FILE = 'log/pureclarity.log';

    /** @var Filesystem $filesystem */
    private $filesystem;

    /** @var ReadInterface */
    private $varDirectory;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
    }

    /**
     * Returns the file size of the log file
     *
     * @return string
     */
    public function getLogFileSize(): string
    {
        return $this->getFileSize(self::LOG_FILE);
    }

    /**
     * Returns whether the log file currently exists.
     *
     * @return bool
     */
    public function isLogFilePresent(): bool
    {
        $varDirectory = $this->getVarDirectoryRead();
        return $varDirectory->isExist(self::LOG_FILE);
    }

    /**
     * Returns a Magento ReadInterface for the var directory
     * @return ReadInterface
     */
    public function getVarDirectoryRead()
    {
        if ($this->varDirectory === null) {
            $this->varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        }
        return $this->varDirectory;
    }

    /**
     * Returns a filesize for a given file
     *
     * @param string $fileName
     * @return string
     */
    public function getFileSize(string $fileName): string
    {
        $fileSize = 0;
        $varDirectory = $this->getVarDirectoryRead();
        if ($varDirectory->isExist($fileName)) {
            $fileSize = $varDirectory->stat($fileName)['size'];
        }

        return $this->humanFileSize($fileSize);
    }

    /**
     * Returns a human readable file size
     *
     * @param int $bytes
     * @return string
     */
    public function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
