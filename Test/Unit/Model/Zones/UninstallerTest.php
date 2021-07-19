<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Zones;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Zones\Uninstaller;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory;
use Magento\Widget\Model\ResourceModel\Widget\Instance\Collection;
use Psr\Log\LoggerInterface;
use Magento\Widget\Model\Widget\Instance;
use Pureclarity\Core\Block\Bmz;

/**
 * Class UninstallerTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Zones\Uninstaller
 */
class UninstallerTest extends TestCase
{
    /** @var Uninstaller $object */
    private $object;

    /** @var MockObject|CollectionFactory $collectionFactory */
    private $collectionFactory;

    /** @var MockObject|Collection $collection */
    private $collection;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactory->method('create')->willReturn($this->collection);

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Uninstaller($this->collectionFactory, $this->logger);
    }

    /**
     * Tests that the class is created correctly.
     */
    public function testInstance(): void
    {
        $this->assertInstanceOf(Uninstaller::class, $this->object);
    }

    /**
     * Tests that uninstall behaves correctly when no widgets returned.
     */
    public function testUninstallNoDelete(): void
    {
        $widget = $this->getMockBuilder(Instance::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->method('addFilter')
            ->with('instance_type', Bmz::class);

        $this->collection->method('getItems')
            ->willReturn([]);

        $widget->expects(self::never())
            ->method('delete');

        $this->object->uninstall();
    }

    /**
     * Tests that uninstall behaves correctly when widgets are returned.
     */
    public function testUninstallWithDelete(): void
    {
        $widget = $this->getMockBuilder(Instance::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->method('addFilter')
            ->with('instance_type', Bmz::class);

        $this->collection->method('getItems')
            ->willReturn([$widget]);

        $widget->expects(self::once())
            ->method('delete');

        $this->object->uninstall();
    }

    /**
     * Tests that uninstall handles exceptions correctly.
     */
    public function testUninstallWithException(): void
    {
        $widget = $this->getMockBuilder(Instance::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->method('addFilter')
            ->with('instance_type', Bmz::class);

        $this->collection->method('getItems')
            ->willReturn([$widget]);

        $widget->expects(self::once())
            ->method('delete')
            ->willThrowException(new \Exception('An error'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity error uninstalling Zones: An error');

        $this->object->uninstall();
    }
}
