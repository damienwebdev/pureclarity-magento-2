<?php
namespace Pureclarity\Core\Setup;

use Pureclarity\Core\Model\CmsBlock;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    /**
     * Constructor to inject dependencies into class.
     *
     * @param \Pureclarity\Core\Model\CmsBlock $cmsBlock CMS block
     */
    public function __construct(CmsBlock $cmsBlock)
    {
        $this->cmsBlock = $cmsBlock;
    }

    /**
     * Uninstalls PureClarity.
     *
     * @param Magento\Framework\Setup\SchemaSetupInterface   $setup   Schema setup interface.
     * @param Magento\Framework\Setup\ModuleContextInterface $context Module context interface.
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
