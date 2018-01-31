<?php

namespace Pureclarity\Core\Setup;
 
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;
 
class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;
 
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }
 
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
 
        // Add PureClarity CATEGORY Attribute group
        $eavSetup->addAttributeGroup(\Magento\Catalog\Model\Category::ENTITY, 'Default', 'PureClarity', 1000);

        // Add attribute for secondary image is added
        $eavSetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, 'pureclarity_category_image', array(
            'group'         => 'PureClarity',
            'input'         => 'image',
            'type'          => 'varchar',
            'backend'       => 'Pureclarity\Core\Model\Attribute\Backend\Image',
            'label'         => 'PureClarity image',
            'visible'       => 1,
            'required'      => 0,
            'user_defined'  => 1,
            'sort_order'    => 6,
            'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE
        ));

        // Add attribute for hiding product from recommenders
        $eavSetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, 'pureclarity_hide_from_feed', array(
            'group'         => 'PureClarity',
            'input'         => 'boolean',
            'type'          => 'int',
            'backend'       => '',
            'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'label'         => 'Exclude from recommenders',
            'visible'       => 1,
            'required'      => 0,
            'user_defined'  => 1,
            'default'       => 0,
            'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'visible_on_front' => true
        ));



        // Add PureClarity PRODUCT Attribute group
        $eavSetup->addAttributeGroup(\Magento\Catalog\Model\Product::ENTITY, 'Default', 'PureClarity', 1000);

        // Add attribute for Search Tags
        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'pureclarity_search_tags', array(
            'group'         => 'PureClarity',
            'input'         => 'text',
            'type'          => 'text',
            'label'         => 'Search tags',
            'backend'       => '',
            'visible'       => 1,
            'required'      => 0,
            'user_defined'  => 1,
            'default'       => '',
            'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'visible_on_front' => true
        ));

        // Add attribute for exluding product from recommenders
        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'pureclarity_exc_rec', array(
            'group'         => 'PureClarity',
            'input'         => 'boolean',
            'type'          => 'int',
            'label'         => 'Exclude from recommenders',
            'backend'       => '',
            'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'visible'       => 1,
            'required'      => 0,
            'user_defined'  => 1,
            'default'       => 0,
            'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'visible_on_front' => true
        ));

        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'pureclarity_newarrival', array(
            'group'         => 'PureClarity',
            'input'         => 'boolean',
            'type'          => 'int',
            'label'         => 'New arrival',
            'backend'       => '',
            'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'visible'       => 1,
            'required'      => 0,
            'user_defined'  => 1,
            'default'       => 0,
            'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'visible_on_front' => true
        ));

        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'pureclarity_onoffer', array(
            'group'         => 'PureClarity',
            'input'         => 'boolean',
            'type'          => 'int',
            'label'         => 'On offer',
            'backend'       => '',
            'source'        => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'visible'       => 1,
            'required'      => 0,
            'user_defined'  => 1,
            'default'       => 0,
            'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'visible_on_front' => true
        ));

        // Add option for Image Overlay
        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'pureclarity_overlay_image', array(
            'input'         => 'media_image',
            'type'          => 'varchar',
            'label'         => 'PureClarity Overlay Image',
            'frontend'      => 'Magento\Catalog\Model\Product\Attribute\Frontend\Image',
            'visible'       => 1,
            'required'      => 0,
            'user_defined'  => 1,
            'global'        => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE
        ));

 
        $setup->endSetup();
    }
}