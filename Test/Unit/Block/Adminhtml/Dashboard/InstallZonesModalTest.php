<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\Block\Adminhtml\Dashboard\InstallZonesModal;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use Pureclarity\Core\ViewModel\Adminhtml\Themes;

/**
 * Class DataTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class InstallZonesModalTest extends TestCase
{
    /** @var InstallZonesModal $object */
    private $object;

    /** @var Context $context */
    private $context;

    /** @var Stores $storesViewModel */
    private $storesViewModel;

    /** @var Themes $themesViewModel */
    private $themesViewModel;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storesViewModel = $this->getMockBuilder(Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->themesViewModel = $this->getMockBuilder(Themes::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new InstallZonesModal(
            $this->context,
            $this->storesViewModel,
            $this->themesViewModel
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(InstallZonesModal::class, $this->object);
    }

    public function testTemplate()
    {
        $this->assertInstanceOf(Template::class, $this->object);
    }

    public function testGetPureclarityStoresViewModel()
    {
        $this->assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }

    public function testGetPureclarityThemesViewModel()
    {
        $this->assertInstanceOf(Themes::class, $this->object->getPureclarityThemesViewModel());
    }
}
