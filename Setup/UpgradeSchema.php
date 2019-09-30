<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace PureClarity\Core\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;
use Zend_Db_Exception;

/**
 * Class UpgradeSchema
 *
 * Runs upgrades to Schema based on PureClarity module version
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Checks to see if a version change needs to trigger an upgrade
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @throws Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $setup->startSetup();
            $this->createStateTable($setup);
            $setup->endSetup();
        }
    }

    /**
     * Creates the pureclarity_state table
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws Zend_Db_Exception
     */
    private function createStateTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('pureclarity_state'))
            ->addColumn(
                'state_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'PureClarity State ID'
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                35,
                ['nullable' => false, 'default' => ''],
                'State Name'
            )
            ->addColumn(
                'value',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'State Value'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => '0'],
                'State Store ID'
            )
            ->addIndex(
                $setup->getIdxName('pureclarity_state', ['name', 'store_id']),
                ['name', 'store_id']
            )->setComment('PureClarity State Table');

        $setup->getConnection()->createTable($table);
    }
}
