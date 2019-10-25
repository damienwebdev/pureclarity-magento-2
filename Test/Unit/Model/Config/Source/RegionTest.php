<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Config\Source\Region;

/**
 * Class RegionTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Config\Source\Region
 */
class RegionTest extends TestCase
{
    /** @var Region $object */
    private $object;

    protected function setUp()
    {
        $this->object = new Region();
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Region::class, $this->object);
    }

    public function testInterface()
    {
        $this->assertInstanceOf(OptionSourceInterface::class, $this->object);
    }

    public function testGetValidRegions()
    {
        $regions = $this->object->getValidRegions();
        $this->assertEquals(2, count($regions));
        $this->assertEquals('Europe', $regions[1]);
        $this->assertEquals('USA', $regions[4]);
    }

    public function testToOptionArray()
    {
        $regions = $this->object->toOptionArray();
        $this->assertEquals(2, count($regions));
        $this->assertEquals(
            [
                'label' => 'Europe',
                'value' => 1
            ],
            $regions[0]
        );
        $this->assertEquals(
            [
                'label' => 'USA',
                'value' => 4
            ],
            $regions[1]
        );
    }
}
