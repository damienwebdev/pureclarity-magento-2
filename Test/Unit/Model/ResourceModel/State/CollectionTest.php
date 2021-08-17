<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\ResourceModel\State;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\ResourceModel\State\Collection;
use Pureclarity\Core\Model\State as StateModel;
use Pureclarity\Core\Model\ResourceModel\State as StateResource;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CollectionTest
 *
 * Tests the methods in \Pureclarity\Core\Model\ResourceModel\State\Collection
 */
class CollectionTest extends TestCase
{
    /** @var Collection $object */
    private $object;

    /** @var MockObject|EntityFactoryInterface $entityFactory */
    private $entityFactory;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    /** @var MockObject|FetchStrategyInterface $fetchStrategy */
    private $fetchStrategy;

    /** @var MockObject|ManagerInterface $eventManager */
    private $eventManager;

    /** @var MockObject|AdapterInterface $connection */
    private $connection;

    /** @var MockObject|AbstractDb $resource */
    private $resource;

    protected function setUp(): void
    {
        $this->entityFactory = $this->createMock(EntityFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->fetchStrategy = $this->createMock(FetchStrategyInterface::class);
        $this->eventManager = $this->createMock(ManagerInterface::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->resource = $this->createMock(AbstractDb::class);
        $select = $this->createMock(Select::class);

        $this->resource->expects($this->any())->method('getConnection')->willReturn($this->connection);
        $this->connection->expects($this->any())->method('select')->willReturn($select);

        $this->object = new Collection(
            $this->entityFactory,
            $this->logger,
            $this->fetchStrategy,
            $this->eventManager,
            $this->connection,
            $this->resource
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Collection::class, $this->object);
    }

    public function testModelName()
    {
        $this->assertEquals(StateModel::class, $this->object->getModelName());
    }

    public function testResourceModelName()
    {
        $this->assertEquals(StateResource::class, $this->object->getResourceModelName());
    }
}
