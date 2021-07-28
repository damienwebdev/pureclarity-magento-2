<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Logger;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Logger\Handler;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Filesystem\DriverInterface;
use Pureclarity\Core\Model\CoreConfig;
use Monolog\Logger;

/**
 * Class HandlerTest
 *
 * Tests the methods in \Pureclarity\Core\Logger\HandlerTest
 */
class HandlerTest extends TestCase
{
    /** @var Handler $object */
    private $object;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|DriverInterface */
    private $filesystem;

    protected function setUp(): void
    {
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->filesystem = $this->createMock(DriverInterface::class);

        $this->object = new Handler(
            $this->coreConfig,
            $this->filesystem
        );
    }

    /**
     * Test that class set up correctly
     */
    public function testCoreConfigInstance(): void
    {
        $this->assertInstanceOf(Handler::class, $this->object);
    }

    /**
     * Tests write writes non debug logs
     */
    public function testWriteNotDebug(): void
    {
        $log = [
            'level' => Logger::INFO,
            'formatted' => false
        ];

        $this->coreConfig->expects(self::never())
            ->method('isDebugLoggingEnabled');

        $this->filesystem->expects(self::once())
            ->method('getParentDirectory');

        $this->object->write($log);
    }

    /**
     * Tests write does not write logs if debug being logged and debug logging disabled
     */
    public function testWriteDebugDisabled(): void
    {
        $log = [
            'level' => Logger::DEBUG,
            'formatted' => false
        ];

        $this->coreConfig->expects(self::once())
            ->method('isDebugLoggingEnabled')
            ->willReturn(false);

        $this->filesystem->expects(self::never())
            ->method('getParentDirectory');

        $this->object->write($log);
    }

    /**
     * Tests write does write logs if debug being logged and debug logging enable
     */
    public function testWriteDebugEnabled(): void
    {
        $log = [
            'level' => Logger::DEBUG,
            'formatted' => false
        ];

        $this->coreConfig->expects(self::once())
            ->method('isDebugLoggingEnabled')
            ->willReturn(true);

        $this->filesystem->expects(self::once())
            ->method('getParentDirectory');

        $this->object->write($log);
    }
}
