<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml;

use Magento\Framework\View\Design\Theme\LabelFactory;
use Magento\Framework\View\Design\Theme\Label;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\ViewModel\Adminhtml\Themes;

/**
 * Class DataTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class ThemesTest extends TestCase
{
    /** @var Themes $object */
    private $object;

    /** @var LabelFactory $labelFactory */
    private $labelFactory;

    /** @var Label $label */
    private $label;

    protected function setUp()
    {
        $this->labelFactory = $this->getMockBuilder(LabelFactory::class)->disableOriginalConstructor()->getMock();
        $this->label = $this->getMockBuilder(Label::class)->disableOriginalConstructor()->getMock();

        $this->object = new Themes($this->labelFactory);
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Themes::class, $this->object);
    }

    public function testGetThemes()
    {
        $expected = [['value' => '', 'label' => 'Theme 1']];

        $this->label->expects($this->any())
            ->method('getLabelsCollection')
            ->willReturn($expected);

        $this->labelFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->label);

        $this->assertEquals($expected, $this->object->getThemes());
    }
}
