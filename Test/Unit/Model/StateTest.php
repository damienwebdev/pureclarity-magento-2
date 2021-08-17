<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\Data\StateInterface;
use Pureclarity\Core\Model\State;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Pureclarity\Core\Model\ResourceModel\State as StateResource;

/**
 * Class StateTest
 *
 * Tests the methods in \Pureclarity\Core\Model\State
 */
class StateTest extends TestCase
{
    /** @var State $object */
    private $object;

    protected function setUp(): void
    {
        $context = $this->createMock(Context::class);
        $registry = $this->createMock(Registry::class);
        $resourceModel = $this->createMock(StateResource::class);

        $this->object = new State($context, $registry, $resourceModel);
    }

    public function testStateInstance()
    {
        $this->assertInstanceOf(State::class, $this->object);
    }

    public function testStateInterface()
    {
        $this->assertInstanceOf(StateInterface::class, $this->object);
    }

    public function testGetIdIsNull()
    {
        $this->assertEquals($this->object->getId(), null);
    }

    public function testSetId()
    {
        $this->object->setId(1);
        $this->assertEquals($this->object->getId(), 1);
    }

    public function testGetNameIsNull()
    {
        $this->assertEquals($this->object->getName(), null);
    }

    public function testSetName()
    {
        $this->object->setName('some_name');
        $this->assertEquals($this->object->getName(), 'some_name');
    }

    public function testGetValueIsNull()
    {
        $this->assertEquals($this->object->getValue(), null);
    }

    public function testSetValue()
    {
        $this->object->setValue('a value');
        $this->assertEquals($this->object->getValue(), 'a value');
    }

    public function testGetStoreIdIsNull()
    {
        $this->assertEquals($this->object->getStoreId(), null);
    }

    public function testSetStoreId()
    {
        $this->object->setStoreId(1);
        $this->assertEquals($this->object->getStoreId(), 1);
    }
}
