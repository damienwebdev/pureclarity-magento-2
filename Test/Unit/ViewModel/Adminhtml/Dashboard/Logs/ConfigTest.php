<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml\Dashboard\Logs;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Logs\Config;
use Pureclarity\Core\Model\CoreConfig;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ConfigTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Config
 */
class ConfigTest extends TestCase
{
    /** @var Config $object */
    private $object;

    /** @var MockObject|CoreConfig $coreConfig */
    private $coreConfig;

    protected function setUp(): void
    {
        $this->coreConfig = $this->createMock(CoreConfig::class);

        $this->object = new Config(
            $this->coreConfig
        );
    }

    /**
     * Tests class set up correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Config::class, $this->object);
    }

    /**
     * Tests that isLoggingEnabled returns true if config enabled
     */
    public function testIsLoggingEnabledTrue(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isDebugLoggingEnabled')
            ->willReturn(true);

        self::assertEquals(true, $this->object->isLoggingEnabled());
    }

    /**
     * Tests that isLoggingEnabled returns false if config disabled
     */
    public function testIsLoggingEnabledFalse(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isDebugLoggingEnabled')
            ->willReturn(false);

        self::assertEquals(false, $this->object->isLoggingEnabled());
    }

    /**
     * Tests that multiple calls to isLoggingEnabled still only calls CoreConfig once
     */
    public function testIsLoggingEnabledOneCall(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isDebugLoggingEnabled')
            ->willReturn(false);

        self::assertEquals(false, $this->object->isLoggingEnabled());
        self::assertEquals(false, $this->object->isLoggingEnabled());
    }
}
