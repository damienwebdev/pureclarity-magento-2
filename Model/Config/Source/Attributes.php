<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;

/**
 * Class Attribute
 *
 * Product attribute dropdown used in config dropdowns
 */
class Attributes implements OptionSourceInterface
{
    /**
     * Protected Attributes - attributes that should not be excluded
     *
     * @var string[]
     */
    private $protectedAttributes = [
        'prices',
        'price',
        'category_ids',
        'sku',
        'description',
        'title',
        'pureclarity_exc_rec',
        'pureclarity_newarrival',
        'pureclarity_onoffer',
        'pureclarity_overlay_image',
        'pureclarity_search_tags'
    ];

    /** @var array[] $attributes */
    private $attributes = [];

    /** @var ProductAttributeCollectionFactory $productAttributeCollectionFactory */
    private $productAttributeCollectionFactory;

    /**
     * @param ProductAttributeCollectionFactory $productAttributeCollectionFactory
     */
    public function __construct(
        ProductAttributeCollectionFactory $productAttributeCollectionFactory
    ) {
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
    }

    /**
     * Loads the attributes and returns them in a format usable by Magento config dropdowns
     *
     * @return array|array[]
     */
    public function toOptionArray()
    {
        $attributes = $this->productAttributeCollectionFactory->create()
            ->addFieldToFilter('attribute_code', ['nin' => $this->protectedAttributes])
            ->addFieldToFilter('frontend_label', ['neq' => ''])
            ->setOrder('attribute_code', 'asc')
            ->getItems();

        // Get list of attributes to include
        foreach ($attributes as $attribute) {
            /** @var $attribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
            $code = $attribute->getAttributeCode();

            if (!empty($attribute->getFrontendLabel())) {
                $this->attributes[$code] = [
                    'value' => $code,
                    'label' => $code . ' - ' . $attribute->getFrontendLabel(),
                ];
            }
        }

        return $this->attributes;
    }
}
