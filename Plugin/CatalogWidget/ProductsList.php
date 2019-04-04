<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Plugin\CatalogWidget;

use Magento\CatalogWidget\Block\Product\ProductsList as CatalogWidgetProductsList;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductsList
{
    /**
     * Adds a custom sort order to the widget collection
     * if the relevant data is present on the collection object
     *
     * @param CatalogWidgetProductsList $subject
     * @param Collection $collection
     * @return Collection
     */
    public function afterCreateCollection(
        CatalogWidgetProductsList $subject,
        Collection $collection
    ) {
        $skuData = $subject->getData('pureclarity_custom_sku_order');
        if (!empty($skuData) && is_array($skuData)) {
            $skus = $this->sanitize($collection, $subject->getData('pureclarity_custom_sku_order'));
            $sortBy = 'FIELD(sku, ' . implode(',', $skus) . ')';
            $collection->getSelect()->order($sortBy);
        }
        
        return $collection;
    }
    
    /**
     * Sanitizes the SKU array to prevent SQL injection attacks
     *
     * @param Collection $collection
     * @param string[] $skus
     * @return string[]
     */
    private function sanitize(Collection $collection, array $skus)
    {
        $connection = $collection->getConnection();
        $sanitizedSkus = [];
        
        foreach ($skus as $sku) {
            $sanitizedSkus[] = $connection->quote($sku);
        }
        return $sanitizedSkus;
    }
}
