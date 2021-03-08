<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Cron;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Cron\RunDeltas;
use Pureclarity\Core\Model\Cron;

/**
 * Class RunDeltasTest
 *
 * Tests the methods in \Pureclarity\Core\Cron\RunDeltas
 */
class RunDeltasTest extends TestCase
{
    /** @var MockObject|RunDeltas $object */
    private $object;

    /** @var MockObject|Cron $cron */
    private $cron;

    /**
     * Sets up RunDeltas with dependencies
     */
    protected function setUp()
    {
        $this->cron = $this->getMockBuilder(Cron::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new RunDeltas(
            $this->cron
        );
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(RunDeltas::class, $this->object);
    }

    /**
     * Tests how execute handles a single store setup with no signup request present
     */
    public function testExecute()
    {
        $this->cron->expects(self::once())
            ->method('reindexData');

        $this->object->execute();
    }
}
