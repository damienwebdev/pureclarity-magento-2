<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Plugin\Minification;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Plugin\Minification\Excludes;
use Magento\Framework\View\Asset\Minification;

/**
 * Class ExcludesTest
 *
 * Tests the methods in \Pureclarity\Core\Plugin\Minification\Excludes
 */
class ExcludesTest extends TestCase
{
    /** @var Excludes $object */
    private $object;

    protected function setUp()
    {
        $this->object = new Excludes();
    }

    /**
     * Tests object gets instantiated correctly
     */
    public function testStateInstance()
    {
        self::assertInstanceOf(Excludes::class, $this->object);
    }

    /**
     * Tests that when the content type is js, then our excludes are added
     */
    public function testAfterGetExcludesJs()
    {
        $subject = $this->getMockBuilder(Minification::class)->disableOriginalConstructor()->getMock();

        $orig = [
            'anotherexclude.js'
        ];

        $expected = [
            'anotherexclude.js',
            'socket\.io',
            'jssor\.slider\.mini'
        ];
        self::assertEquals($expected, $this->object->afterGetExcludes($subject, $orig, 'js'));
    }

    /**
     * Tests that when the content type is not js, then our excludes are not added
     */
    public function testAfterGetExcludesNonJs()
    {
        $subject = $this->getMockBuilder(Minification::class)->disableOriginalConstructor()->getMock();

        $orig = [
            'anotherexclude.js'
        ];

        self::assertEquals($orig, $this->object->afterGetExcludes($subject, $orig, 'css'));
    }
}
