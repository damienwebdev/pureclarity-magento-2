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
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->collection = $this->createMock(Collection::class);

        $this->collectionFactory->method('create')->willReturn($this->collection);

        $this->logger = $this->createMock(LoggerInterface::class);

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
        $widget = $this->createMock(Instance::class);

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
        $widget = $this->createMock(Instance::class);

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
        $widget = $this->createMock(Instance::class);

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
