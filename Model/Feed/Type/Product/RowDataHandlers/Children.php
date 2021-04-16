<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type;

/**
 * Class Children
 *
 * Handles loading child products for the given product
 */
class Children
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var ConfigurableFactory */
    private $configurableFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ConfigurableFactory $configurableFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ConfigurableFactory $configurableFactory
    ) {
        $this->collectionFactory   = $collectionFactory;
        $this->configurableFactory = $configurableFactory;
    }

    /**
     * Load child products for the given product.
     *
     * @param Product|ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    public function loadChildData($product): array
    {
        $childProducts = [];
        switch ($product->getTypeId()) {
            case Configurable::TYPE_CODE:
                $configProduct = $this->configurableFactory->create();
                $childIds = $configProduct->getChildrenIds($product->getId());
                if (count($childIds[0]) > 0) {
                    $collection = $this->collectionFactory->create();
                    $collection->addAttributeToSelect('*');
                    $collection->addFieldToFilter('entity_id', ['in' => $childIds[0]]);
                    $childProducts = $collection->getItems();
                } else {
                    throw new NoSuchEntityException(__('Cannot use configurable with no children'));
                }
                break;
            case Grouped::TYPE_CODE:
                $type = $product->getTypeInstance();
                $childProducts = $type->getAssociatedProducts($product);
                break;
            case Type::TYPE_CODE: // Bundle
                $type = $product->getTypeInstance();
                $childProducts = $type->getSelectionsCollection(
                    $type->getOptionsIds($product),
                    $product
                );
                $childProducts = $childProducts->getItems();
                break;
        }

        return $childProducts;
    }
}
