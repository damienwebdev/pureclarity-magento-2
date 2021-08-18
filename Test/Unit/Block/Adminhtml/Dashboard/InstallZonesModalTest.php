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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class InstallZonesModalTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Dashboard\InstallZonesModal
 */
class InstallZonesModalTest extends TestCase
{
    /** @var InstallZonesModal $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|Stores $storesViewModel */
    private $storesViewModel;

    /** @var MockObject|Themes $themesViewModel */
    private $themesViewModel;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->storesViewModel = $this->createMock(Stores::class);
        $this->themesViewModel = $this->createMock(Themes::class);

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
