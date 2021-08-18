<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml\Dashboard\Logs;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Logs\File;
use Pureclarity\Core\Model\CoreConfig;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class FileTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Dashboard\File
 */
class FileTest extends TestCase
{
    /** @var File $object */
    private $object;

    /** @var MockObject|CoreConfig $coreConfig */
    private $coreConfig;

    /** @var MockObject|Filesystem $filesystem */
    private $filesystem;

    /** @var MockObject|ReadInterface $varDirectory */
    private $varDirectory;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->varDirectory = $this->createMock(ReadInterface::class);

        $this->filesystem->method('getDirectoryRead')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn($this->varDirectory);

        $this->object = new File(
            $this->filesystem
        );
    }

    /**
     * Tests class set up correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(File::class, $this->object);
    }

    /**
     * Tests that isLogFilePresent returns expected value
     */
    public function testIsLogFilePresentTrue(): void
    {
        $this->varDirectory->expects(self::once())
            ->method('isExist')
            ->with('log/pureclarity.log')
            ->willReturn(true);

        self::assertEquals(true, $this->object->isLogFilePresent());
    }

    /**
     * Tests that isLogFilePresent returns expected value
     */
    public function testIsLogFilePresentFalse(): void
    {
        $this->varDirectory->expects(self::once())
            ->method('isExist')
            ->with('log/pureclarity.log')
            ->willReturn(false);

        self::assertEquals(false, $this->object->isLogFilePresent());
    }

    /**
     * Tests that getLogFileSize returns expected B value
     */
    public function testGetLogFileSizeB(): void
    {
        $this->varDirectory->expects(self::once())
            ->method('isExist')
            ->with('log/pureclarity.log')
            ->willReturn(true);

        $this->varDirectory->expects(self::once())
            ->method('stat')
            ->with('log/pureclarity.log')
            ->willReturn(['size' => 123]);

        self::assertEquals('123 B', $this->object->getLogFileSize());
    }

    /**
     * Tests that getLogFileSize returns expected KB value
     */
    public function testGetLogFileSizeKB(): void
    {
        $this->varDirectory->expects(self::once())
            ->method('isExist')
            ->with('log/pureclarity.log')
            ->willReturn(true);

        $this->varDirectory->expects(self::once())
            ->method('stat')
            ->with('log/pureclarity.log')
            ->willReturn(['size' => 10245]);

        self::assertEquals('10 KB', $this->object->getLogFileSize());
    }

    /**
     * Tests that getLogFileSize returns expected MB value
     */
    public function testGetLogFileSizeMB(): void
    {
        $this->varDirectory->expects(self::once())
            ->method('isExist')
            ->with('log/pureclarity.log')
            ->willReturn(true);

        $this->varDirectory->expects(self::once())
            ->method('stat')
            ->with('log/pureclarity.log')
            ->willReturn(['size' => 123456789]);

        self::assertEquals('117.74 MB', $this->object->getLogFileSize());
    }
}
