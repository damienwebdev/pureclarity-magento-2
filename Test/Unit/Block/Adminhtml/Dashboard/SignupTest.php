<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\Block\Adminhtml\Dashboard\Signup;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Regions;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Store;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\State;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;

/**
 * Class SignupTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Dashboard\Signup
 */
class SignupTest extends TestCase
{
    /** @var Signup $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|Stores $storesViewModel */
    private $storesViewModel;

    /** @var MockObject|Regions $regionsViewModel */
    private $regionsViewModel;

    /** @var MockObject|Store $storeViewModel */
    private $storeViewModel;

    /** @var MockObject|FormKey $formKey */
    private $formKey;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->formKey = $this->createMock(FormKey::class);

        $this->context->expects(self::any())
            ->method('getFormKey')
            ->willReturn($this->formKey);

        $request = $this->createMock(RequestInterface::class);
        $assetRepo = $this->createMock(Repository::class);

        $this->context->method('getFormKey')
            ->willReturn($this->formKey);

        $this->context->method('getRequest')
            ->willReturn($request);

        $assetRepo->method('getUrlWithParams')->willReturnCallback(function ($param) {
            return str_replace('Pureclarity_Core::images/', 'https://www.test.com/', $param);
        });

        $this->context->method('getAssetRepository')
            ->willReturn($assetRepo);

        $this->storesViewModel = $this->createMock(Stores::class);
        $this->regionsViewModel = $this->createMock(Regions::class);
        $this->storeViewModel = $this->createMock(Store::class);
        $stateViewModel = $this->createMock(State::class);

        $this->object = new Signup(
            $this->context,
            $this->storesViewModel,
            $this->regionsViewModel,
            $this->storeViewModel,
            $stateViewModel
        );
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(Signup::class, $this->object);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testTemplate()
    {
        self::assertInstanceOf(Template::class, $this->object);
    }

    /**
     * Tests that getPureclarityStoresViewModel returns the right class
     */
    public function testGetPureclarityStoresViewModel()
    {
        self::assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }

    /**
     * Tests that testGetPureclarityRegionsViewModel returns the right class
     */
    public function testGetPureclarityRegionsViewModel()
    {
        self::assertInstanceOf(Regions::class, $this->object->getPureclarityRegionsViewModel());
    }

    /**
     * Tests that getPureclarityStoreViewModel returns the right class
     */
    public function testGetPureclarityStoreViewModel()
    {
        self::assertInstanceOf(Store::class, $this->object->getPureclarityStoreViewModel());
    }

    /**
     * Tests that getPureclarityStateViewModel returns the right class
     */
    public function testGetPureclarityStateViewModel()
    {
        self::assertInstanceOf(State::class, $this->object->getPureclarityStateViewModel());
    }

    /**
     * Tests that getFormKey returns the formkey from Magento
     */
    public function testGetFormKey()
    {
        $formKey = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $this->formKey->expects(self::once())
            ->method('getFormKey')
            ->willReturn($formKey);

        self::assertEquals($formKey, $this->object->getFormKey());
    }

    /**
     * Tests that getFormKey returns the image url provided by Magento
     */
    public function testGetImageUrl()
    {
        $image = $this->object->getImageUrl('image.jpg');

        self::assertEquals('https://www.test.com/image.jpg', $image);
    }
}
