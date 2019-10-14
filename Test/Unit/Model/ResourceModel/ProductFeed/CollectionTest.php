<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\ResourceModel\ProductFeed;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\ResourceModel\ProductFeed\Collection;
use Pureclarity\Core\Model\ProductFeed;
use Pureclarity\Core\Model\ResourceModel\ProductFeed as ProductFeedResource;
use PHPUnit\Framework\MockObject\MockObject;

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
        $this->assertEquals(ProductFeed::class, $this->object->getModelName());
    }

    public function testResourceModelName()
    {
        $this->assertEquals(ProductFeedResource::class, $this->object->getResourceModelName());
    }
}
