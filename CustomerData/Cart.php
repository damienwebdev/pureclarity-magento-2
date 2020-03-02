<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\CustomerData;

use \Magento\Customer\CustomerData\SectionSourceInterface;
use \Magento\Checkout\Model\Cart as CartModel;

/**
 * Class Cart
 *
 * Data model for PureClarity cart-update event (see frontend/section.xml for usages)
 */
class Cart implements SectionSourceInterface
{
    /** @var CartModel $cart */
    private $cart;

    /**
     * @param CartModel $cart
     */
    public function __construct(
        CartModel $cart
    ) {
        $this->cart = $cart;
    }
    
    /**
     * Prepares data for the set_basket event based on the customers cart contents
     *
     * @return mixed[]
     */
    public function getSectionData()
    {
        $items = [];
        $visibleItems = $this->cart->getQuote()->getAllVisibleItems();
        $allItems = $this->cart->getQuote()->getAllItems();
        foreach ($visibleItems as $item) {
            $items[$item->getItemId()] = [
                'id' => $item->getProductId(),
                'qty' => $item->getQty(),
                'unitprice' => $item->getPrice(),
                'refid' => $item->getItemId(),
                'children' => []
            ];
        }
        foreach ($allItems as $item) {
            if ($item->getParentItemId() && isset($items[$item->getParentItemId()])) {
                $items[$item->getParentItemId()]['children'][] = ["sku" => $item->getSku(), "qty" => $item->getQty()];
            }
        }

        return [
            'items' => array_values($items)
        ];
    }
}
