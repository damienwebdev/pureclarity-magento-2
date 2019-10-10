<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Setup;

use Magento\Framework\DB\Ddl\Table;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Setup\UpgradeSchema;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class DataTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class UpgradeSchemaTest extends TestCase
{
    /** @var UpgradeSchema $object */
    private $object;

    /** @var SchemaSetupInterface $setup */
    private $setup;

    /** @var ModuleContextInterface $context */
    private $context;

    /** @var AdapterInterface $adapter */
    private $adapter;

    /** @var Table $table */
    private $table;

    protected function setUp()
    {
        $this->setup = $this->getMockBuilder(SchemaSetupInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(ModuleContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setup->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapter);

        $this->object = new UpgradeSchema();
    }

    public function testInstance()
    {
        $this->assertInstanceOf(UpgradeSchema::class, $this->object);
    }

    public function testInterface()
    {
        $this->assertInstanceOf(UpgradeSchemaInterface::class, $this->object);
    }

    public function testUpgrade200()
    {
        $this->context->expects($this->at(0))
            ->method('getVersion')
            ->willReturn('1.0.0');

        $this->setup->expects($this->once())->method('startSetup');
        $this->setup->expects($this->once())->method('endSetup');
        $this->setup->expects($this->once())->method('getIdxName')->willReturn('index_name');
        $this->setup->expects($this->once())->method('getTable')->willReturn('pureclarity_state');

        $this->adapter->expects($this->once())
            ->method('newTable')
            ->with('pureclarity_state')
            ->willReturn($this->table);

        $this->table->expects($this->at(0))
            ->method('addColumn')
            ->with(
                'state_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'PureClarity State ID'
            )
            ->willReturn($this->table);

        $this->table->expects($this->at(1))
            ->method('addColumn')
            ->with(
                'name',
                Table::TYPE_TEXT,
                35,
                ['nullable' => false, 'default' => ''],
                'State Name'
            )
            ->willReturn($this->table);

        $this->table->expects($this->at(2))
            ->method('addColumn')
            ->with(
                'value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'State Value'
            )
            ->willReturn($this->table);

        $this->table->expects($this->at(3))
            ->method('addColumn')
            ->with(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => '0'],
                'State Store ID'
            )
            ->willReturn($this->table);

        $this->table->expects($this->at(4))
            ->method('addIndex')
            ->with(
                'index_name',
                ['name', 'store_id']
            )
            ->willReturn($this->table);

        $this->table->expects($this->at(5))
            ->method('setComment')
            ->with(
                'PureClarity State Table'
            )
            ->willReturn($this->table);

        $this->adapter->expects($this->once())
            ->method('createTable');

        $this->object->upgrade($this->setup, $this->context);
    }

    public function testNoUpgrade()
    {
        $this->context->expects($this->at(0))
            ->method('getVersion')
            ->willReturn('9.9.9');

        $this->setup->expects($this->never())->method('startSetup');
        $this->setup->expects($this->never())->method('endSetup');
        $this->object->upgrade($this->setup, $this->context);
    }
}
