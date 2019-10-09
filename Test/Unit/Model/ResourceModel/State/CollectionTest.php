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

/**
 * Class StateTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class CollectionTest extends TestCase
{
    /** @var Collection $object */
    private $object;

    /** @var EntityFactoryInterface $entityFactory */
    private $entityFactory;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var FetchStrategyInterface $fetchStrategy */
    private $fetchStrategy;

    /** @var ManagerInterface $eventManager */
    private $eventManager;

    /** @var AdapterInterface $connection */
    private $connection;

    /** @var AbstractDb $resource */
    private $resource;

    protected function setUp()
    {
        $this->entityFactory = $this->getMockBuilder(EntityFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fetchStrategy = $this->getMockBuilder(FetchStrategyInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resource = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

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
