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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ThemesTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Themes
 */
class ThemesTest extends TestCase
{
    /** @var Themes $object */
    private $object;

    /** @var MockObject|LabelFactory $labelFactory */
    private $labelFactory;

    /** @var MockObject|Label $label */
    private $label;

    protected function setUp(): void
    {
        $this->labelFactory = $this->createMock(LabelFactory::class);
        $this->label = $this->createMock(Label::class);

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
