<?php

namespace Pureclarity\Core\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * Installs the database schema required for PureClarity.
     *
     * @param Magento\Framework\Setup\SchemaSetupInterface   $setup   Schema setup interface.
     * @param Magento\Framework\Setup\ModuleContextInterface $context Module context interface.
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        // Create Product Feed Table
        $table = $installer->getTable('pureclarity_productfeed');

        if ($installer->tableExists($table)) {
            $installer->getConnection()->dropTable($table);
        }

        // Create tbale
        $prodFeedTable = $installer->getConnection()->newTable($table);

        $prodFeedTable
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'primary'  => true,
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                ],
                'Auto Increment ID'
            )
            ->addColumn(
                'product_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false
                ],
                'Changed Product'
            )
            ->addColumn(
                'token',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true
                ],
                'Token'
            )
            ->addColumn(
                'status_id',
                Table::TYPE_SMALLINT,
                null,
                [
                    'nullable' => true
                ],
                'Status'
            )
            ->addColumn(
                'message',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true
                ],
                'Message'
            )
            ->addIndex(
                $installer->getIdxName($table, ['token']),
                ['token']
            )
            ->addIndex(
                $installer->getIdxName($table, ['status_id']),
                ['status_id']
            )
            ->setComment('PureClarity Delta Table');

        $installer->getConnection()->createTable($prodFeedTable);

        $installer->endSetup();
    }
}
