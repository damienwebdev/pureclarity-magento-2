<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Zones;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Zones\Installer;
use Magento\Widget\Model\Widget\InstanceFactory;
use Magento\Widget\Model\Widget\Instance;
use Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory;
use Magento\Widget\Model\ResourceModel\Widget\Instance\Collection;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Block\Bmz;

/**
 * Class InstallerTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Zones\Installer
 */
class InstallerTest extends TestCase
{
    /** @var Installer $object */
    private $object;

    /** @var MockObject|InstanceFactory $widgetFactory */
    private $widgetFactory;

    /** @var MockObject|Instance $widgetInstance */
    private $widgetInstance;

    /** @var MockObject|CollectionFactory $collectionFactory */
    private $collectionFactory;

    /** @var MockObject|Collection $collection */
    private $collection;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    protected function setUp(): void
    {
        $this->widgetFactory = $this->createMock(InstanceFactory::class);

        $this->widgetInstance = $this->createPartialMock(
            Instance::class,
            [
                'getWidgetReference',
                'setType',
                'setCode',
                'setThemeId',
                'setTitle',
                'setStoreIds',
                'setWidgetParameters',
                'setSortOrder',
                'setPageGroups',
                'save'
            ]
        );

        $this->widgetFactory->method('create')->willReturn($this->widgetInstance);

        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->collection = $this->createMock(Collection::class);

        $this->collectionFactory->method('create')->willReturn($this->collection);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->object = new Installer($this->widgetFactory, $this->collectionFactory, $this->logger);
    }

    /**
     * Tests that the class is created correctly.
     */
    public function testInstance(): void
    {
        $this->assertInstanceOf(Installer::class, $this->object);
    }

    /**
     * Test that install() installs all zones correctly.
     */
    public function testInstall(): void
    {
        $this->collection->method('count')
            ->willReturn(0);

        $this->widgetInstance->expects(self::exactly(10))
            ->method('save');

        $this->object->install(
            [
                'homepage',
                'product_page',
                'basket_page',
                'order_confirmation_page'
            ],
            1,
            1
        );
    }

    /**
     * Test that installZones() installs all zones for the type correctly.
     */
    public function testInstallZones(): void
    {
        $this->collection->method('count')
            ->willReturn(0);

        $this->widgetInstance->expects(self::exactly(4))
            ->method('save');

        $this->object->installZones('homepage', 1, 1);
    }

    /**
     * Test that installZones() installs no zones for the type when already installed.
     */
    public function testInstallZonesNoInstall(): void
    {
        $this->collection->method('count')
            ->willReturn(1);

        $this->widgetInstance->expects(self::never())
            ->method('save');

        $this->object->installZones('homepage', 1, 1);
    }

    /**
     * Test that installZones() handles an exception.
     */
    public function testInstallZonesException(): void
    {
        $this->collection->method('count')
            ->willReturn(0);

        $this->widgetInstance->method('save')
            ->willThrowException(new \Exception('An error'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity Error installing homepage Zones: An error');

        $this->object->installZones('homepage', 1, 1);
    }

    /**
     * Tests that createZone passes the right data to the widget instance
     */
    public function testCreateZone(): void
    {
        $zone = [
            'title' => 'PC Zone HP-01',
            'sort_order' => 0,
            'page_group' => 'pages',
            'group_data' => [
                'block' => 'content.bottom',
                'layout_handle' => 'cms_index_index',
            ],
            'bmz_buffer' => false,
        ];

        $this->widgetInstance->expects(self::at(0))
            ->method('getWidgetReference')
            ->with('code', Bmz::WIDGET_ID, 'type')
            ->willReturn('widget_type');

        $this->widgetInstance->expects(self::at(1))
            ->method('setType')
            ->with('widget_type');

        $this->widgetInstance->expects(self::at(2))
            ->method('setCode')
            ->with(Bmz::WIDGET_ID);

        $this->widgetInstance->expects(self::at(3))
            ->method('setThemeId')
            ->with(1);

        $this->widgetInstance->expects(self::at(4))
            ->method('setTitle')
            ->with('PC Zone HP-01');

        $this->widgetInstance->expects(self::at(5))
            ->method('setStoreIds')
            ->with([1]);

        $this->widgetInstance->expects(self::at(6))
            ->method('setWidgetParameters')
            ->with([
                'bmz_id' => 'HP-01',
                'pc_bmz_buffer' => 0
            ]);

        $this->widgetInstance->expects(self::at(7))
            ->method('setSortOrder')
            ->with(0);

        $this->widgetInstance->expects(self::at(8))
            ->method('setPageGroups')
            ->with([
                [
                    'page_group' => 'pages',
                    'pages' => [
                        'block' => 'content.bottom',
                        'for' => 'all',
                        'layout_handle' => 'cms_index_index',
                        'page_id' => '',
                    ]
                ]
            ]);

        $this->object->createZone('HP-01', $zone, 1, 1);
    }

    /**
     * Tests that doesZoneExist returns the expected value when zone installed already
     */
    public function testDoesZoneExistTrue(): void
    {
        $this->collection->expects(self::at(0))->method('addFilter')->with('title', 'PC Zone HP-01');
        $this->collection->expects(self::at(1))->method('addFilter')->with('theme_id', 1);
        $this->collection->expects(self::at(2))->method('addFilter')->with('store_ids', 1);
        $this->collection->expects(self::at(3))->method('count')->willReturn(1);

        $this->assertEquals(true, $this->object->doesZoneExist('PC Zone HP-01', 1, 1));
    }

    /**
     * Tests that doesZoneExist returns the expected value when zone not installed already
     */
    public function testDoesZoneExistFalse(): void
    {
        $this->collection->expects(self::at(0))->method('addFilter')->with('title', 'PC Zone HP-01');
        $this->collection->expects(self::at(1))->method('addFilter')->with('theme_id', 1);
        $this->collection->expects(self::at(2))->method('addFilter')->with('store_ids', 1);
        $this->collection->expects(self::at(3))->method('count')->willReturn(0);

        $this->assertEquals(false, $this->object->doesZoneExist('PC Zone HP-01', 1, 1));
    }

    /**
     * Tests that getTypeZones returns the expected homepage zones
     */
    public function testGetHomepageZones(): void
    {
        $result = $this->object->getTypeZones('homepage');
        $this->assertEquals(
            [
                'HP-01',
                'HP-02',
                'HP-03',
                'HP-04'
            ],
            array_keys($result)
        );
    }

    /**
     * Tests that getTypeZones returns the expected product page zones
     */
    public function testGetProductPageZones(): void
    {
        $result = $this->object->getTypeZones('product_page');
        $this->assertEquals(
            [
                'PP-01',
                'PP-02'
            ],
            array_keys($result)
        );
    }

    /**
     * Tests that getTypeZones returns the expected basket page zones
     */
    public function testGetBasketPageZones(): void
    {
        $result = $this->object->getTypeZones('basket_page');
        $this->assertEquals(
            [
                'BP-01',
                'BP-02'
            ],
            array_keys($result)
        );
    }

    /**
     * Tests that getTypeZones returns the expected order confirmation page zones
     */
    public function testGetOrderConfirmationPageZones(): void
    {
        $result = $this->object->getTypeZones('order_confirmation_page');
        $this->assertEquals(
            [
                'OC-01',
                'OC-02'
            ],
            array_keys($result)
        );
    }

    /**
     * Tests that testGetZones returns the expected zone types
     */
    public function testGetZones(): void
    {
        $result = $this->object->getZones();
        $this->assertEquals(
            [
                'homepage',
                'product_page',
                'basket_page',
                'order_confirmation_page'
            ],
            array_keys($result)
        );
    }
}
