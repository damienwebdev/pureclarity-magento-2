<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class Stock
 *
 * Gets stock data for the given product
 */
class Stock
{
    /** @var StockRegistryInterface */
    private $stockRegistry;

    /** @var CoreConfig */
    private $coreConfig;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        CoreConfig $coreConfig
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->coreConfig    = $coreConfig;
    }

    /**
     * Returns whether a product is in stock or not
     *
     * @param Product|ProductInterface $product
     * @return string
     */
    public function getStockFlag($product): string
    {
        return $this->stockRegistry->getStockItem($product->getId())->getIsInStock() ? 'true' : 'false';
    }

    /**
     * Returns whether a product should be excluded from recommenders or not
     *
     * @param int $storeId
     * @param string $stockFlag
     * @return bool
     */
    public function isExcluded(int $storeId, string $stockFlag): bool
    {
        return ($stockFlag === 'false' && $this->coreConfig->getExcludeOutOfStockFromRecommenders($storeId));
    }
}
