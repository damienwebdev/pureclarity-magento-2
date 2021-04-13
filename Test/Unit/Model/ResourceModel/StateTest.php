<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\ResourceModel;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\Data\StateInterface;
use Pureclarity\Core\Model\ResourceModel\State;
use Magento\Framework\Model\ResourceModel\Db\Context;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class StateTest
 *
 * Tests the methods in \Pureclarity\Core\Model\ResourceModel\State
 */
class StateTest extends TestCase
{
    /** @var State $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new State($this->context);
    }

    public function testInstance()
    {
        $this->assertInstanceOf(State::class, $this->object);
    }

    public function testIdFieldName()
    {
        $this->assertEquals(StateInterface::ID, $this->object->getIdFieldName());
    }
}
