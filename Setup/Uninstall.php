<?php

namespace Pureclarity\Core\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        $connection = $setup->getConnection();
        $connection->dropTable($setup->getTable('pureclarity_productfeed'));

        $setup->endSetup();
    }
}