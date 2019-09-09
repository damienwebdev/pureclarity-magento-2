<?php
namespace Pureclarity\Core\Setup;
 
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;

class InstallData implements InstallDataInterface
{
    /**
     * Entity attribute value setup factory
     *
     * @var Magento\Eav\Setup\EavSetupFactory
     */
    private $eavSetupFactory;
 
    /**
     * Constructor to inject dependencies into class.
     *
     * @param Magento\Eav\Setup\EavSetupFactory $eavSetupFactory Entity attribute value setup factory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }
 
    /**
     * Installs attributes required for PureClarity.
     *
     * @param ModuleDataSetupInterface $setup   Module data setup interface
     * @param ModuleContextInterface   $context Module context interface
     *
     * @return void
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(
            [
                'setup' => $setup
            ]
        );


        /*
         * Magento 2 does not properly support category attributes until version 2.1 or above
         * so don't install them if on 2.0
         */

        $isMagento20 = defined("\\Magento\\Framework\\AppInterface::VERSION");

        if (! $isMagento20) {
 
            // Add PureClarity CATEGORY Attribute group
            $eavSetup->addAttributeGroup(
                Category::ENTITY,
                'Default',
                'PureClarity',
                1000
            );

            // Add attribute for override image for categories and brands
            $eavSetup->addAttribute(
                Category::ENTITY,
                'pureclarity_category_image',
                [
                    'group' => 'PureClarity',
                    'input' => 'image',
                    'type' => 'varchar',
                    'backend' => 'Pureclarity\Core\Model\Attribute\Backend\Image',
                    'label' => 'PureClarity image',
                    'visible' => 1,
                    'required' => 0,
                    'user_defined' => 1,
                    'sort_order' => 6,
                    'global' => ScopedAttributeInterface::SCOPE_STORE
                ]
            );

            // Add attribute for hiding product from recommenders
            $eavSetup->addAttribute(
                Category::ENTITY,
                'pureclarity_hide_from_feed',
                [
                    'group' => 'PureClarity',
                    'input' => 'boolean',
                    'type' => 'int',
                    'backend' => '',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'label' => 'Exclude from recommenders',
                    'visible' => 1,
                    'required' => 0,
                    'user_defined' => 1,
                    'default' => 0,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible_on_front' => true
                ]
            );
        }

        // Add PureClarity PRODUCT Attribute group
        $eavSetup->addAttributeGroup(
            Product::ENTITY,
            'Default',
            'PureClarity',
            1000
        );

        // Add attribute for Search Tags
        $eavSetup->addAttribute(
            Product::ENTITY,
            'pureclarity_search_tags',
            [
                'group' => 'PureClarity',
                'input' => 'text',
                'type' => 'text',
                'label' => 'Search tags',
                'backend' => '',
                'visible' => 1,
                'required' => 0,
                'user_defined' => 1,
                'default' => '',
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible_on_front' => true
            ]
        );

        // Add attribute for exluding product from recommenders
        $eavSetup->addAttribute(
            Product::ENTITY,
            'pureclarity_exc_rec',
            [
                'group' => 'PureClarity',
                'input' => 'boolean',
                'type' => 'int',
                'label' => 'Exclude from recommenders',
                'backend' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => 1,
                'required' => 0,
                'user_defined' => 1,
                'default' => 0,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible_on_front' => true
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'pureclarity_newarrival',
            [
                'group' => 'PureClarity',
                'input' => 'boolean',
                'type' => 'int',
                'label' => 'New arrival',
                'backend' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => 1,
                'required' => 0,
                'user_defined' => 1,
                'default' => 0,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible_on_front' => true
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'pureclarity_onoffer',
            [
                'group' => 'PureClarity',
                'input' => 'boolean',
                'type' => 'int',
                'label' => 'On offer',
                'backend' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'visible' => 1,
                'required' => 0,
                'user_defined' => 1,
                'default' => 0,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible_on_front' => true
            ]
        );

        // Add option for Image Overlay
        $eavSetup->addAttribute(
            Product::ENTITY,
            'pureclarity_overlay_image',
            [
                'input' => 'media_image',
                'type' => 'varchar',
                'label' => 'PureClarity Overlay Image',
                'frontend' => 'Magento\Catalog\Model\Product\Attribute\Frontend\Image',
                'visible' => 1,
                'required' => 0,
                'user_defined' => 1,
                'global' => ScopedAttributeInterface::SCOPE_STORE
            ]
        );
 
        $setup->endSetup();
    }
}
