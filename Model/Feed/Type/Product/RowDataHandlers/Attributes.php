<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers;

use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

/**
 * Class Attributes
 *
 * Loads attributes for the given store ID, also checks for excluded attributes
 */
class Attributes
{
    /** @var array */
    private $attributesToInclude;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * @param CoreConfig $coreConfig
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CoreConfig $coreConfig,
        CollectionFactory $collectionFactory
    ) {
        $this->coreConfig        = $coreConfig;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Loads product attributes to be collated in the feed.
     * @param int $storeId
     * @return array
     */
    public function loadAttributes(int $storeId): array
    {
        if ($this->attributesToInclude === null) {
            $this->attributesToInclude = [];
            $excludedAttributes = $this->loadExcludedAttributes($storeId);

            // Get Attributes
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('attribute_code', ['nin' => $excludedAttributes]);
            $collection->addFieldToFilter('frontend_label', ['neq' => '']);
            $attributes = $collection->getItems();

            // Get list of attributes to include
            foreach ($attributes as $attribute) {
                /** @var $attribute Attribute */
                $this->attributesToInclude[] = [
                    'code' => $attribute->getAttributeCode(),
                    'label' => $attribute->getFrontendLabel(),
                    'type' => $attribute->getFrontendInput()
                ];
            }
        }

        return $this->attributesToInclude;
    }

    /**
     * Builds an array of attributes to be excluded from the feed.
     *
     * @param int $storeId
     * @return string[]
     */
    public function loadExcludedAttributes(int $storeId): array
    {
        $attributes = ["prices", "price", "category_ids", "sku"];

        $excludedAttributes = $this->coreConfig->getExcludedProductAttributes($storeId);

        if (!empty($excludedAttributes)) {
            $exclusions = explode(',', $excludedAttributes);
            $attributes = array_merge($attributes, $exclusions);
        }

        return $attributes;
    }
}
