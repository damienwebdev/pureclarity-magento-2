<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Log;

use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Log\Delete;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Class DeleteTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Log\Delete
 */
class DeleteTest extends TestCase
{
    /** @var Delete $object */
    private $object;

    /** @var MockObject|DirectoryList $directoryList */
    private $directoryList;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    /** @var MockObject|File */
    private $file;

    protected function setUp(): void
    {
        $this->directoryList = $this->createMock(DirectoryList::class);
        $this->logger        = $this->createMock(LoggerInterface::class);
        $this->file          = $this->createMock(File::class);

        $this->object = new Delete(
            $this->directoryList,
            $this->logger,
            $this->file
        );
    }

    /**
     * Test that class set up correctly
     */
    public function testCoreConfigInstance(): void
    {
        $this->assertInstanceOf(Delete::class, $this->object);
    }

    /**
     * Tests deleteLogs works as expected if file exists
     */
    public function testDeleteLogs(): void
    {
        $this->directoryList->expects(self::once())
            ->method('getPath')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn('/var');

        $this->file->expects(self::once())
            ->method('isExists')
            ->with('/var/log/pureclarity.log')
            ->willReturn(true);

        $this->file->expects(self::once())
            ->method('deleteFile')
            ->with('/var/log/pureclarity.log');

        $this->object->deleteLogs();
    }

    /**
     * Tests deleteLogs works as expected if no file exists
     */
    public function testDeleteLogsNoFile(): void
    {
        $this->directoryList->expects(self::once())
            ->method('getPath')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn('/var');

        $this->file->expects(self::once())
            ->method('isExists')
            ->with('/var/log/pureclarity.log')
            ->willReturn(false);

        $this->file->expects(self::never())
            ->method('deleteFile');

        $this->object->deleteLogs();
    }

    /**
     * Tests deleteLogs works as expected if an exception happens
     */
    public function testDeleteLogsException(): void
    {
        $this->directoryList->expects(self::once())
            ->method('getPath')
            ->with(DirectoryList::VAR_DIR)
            ->willThrowException(new FileSystemException(new Phrase('An error')));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity error deleting log file: An error');

        $this->file->expects(self::never())
            ->method('isExists');

        $this->file->expects(self::never())
            ->method('deleteFile');

        $this->object->deleteLogs();
    }
}
