<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Setup;

use Pureclarity\Core\Model\CmsBlock;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Class Uninstall
 *
 * Removes schema changes from database
 */
class Uninstall implements UninstallInterface
{
    /** @var CmsBlock $cmsBlock */
    private $cmsBlock;

    /**
     * Constructor to inject dependencies into class.
     *
     * @param CmsBlock $cmsBlock
     */
    public function __construct(CmsBlock $cmsBlock)
    {
        $this->cmsBlock = $cmsBlock;
    }

    /**
     * Uninstalls PureClarity.
     *
     * @param SchemaSetupInterface $setup   Schema setup interface.
     * @param ModuleContextInterface $context Module context interface.
     *
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        $connection = $setup->getConnection();
        $connection->dropTable($setup->getTable('pureclarity_productfeed'));

        $this->cmsBlock->uninstall();

        $setup->endSetup();
    }
}
