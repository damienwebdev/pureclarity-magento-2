<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Pureclarity\Core\CustomerData;

use \Magento\Customer\CustomerData\SectionSourceInterface;
use \Psr\Log\LoggerInterface;
use \Magento\Checkout\Model\Cart as CartModel;

class Cart implements SectionSourceInterface
{
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    
    /** @var \Magento\Checkout\Model\Cart */
    private $cart;

    public function __construct(
        LoggerInterface $logger,
        CartModel $cart
    ) {
        $this->cart = $cart;
        $this->logger = $logger;
    }
    
    /**
     * Prepares data for the set_basket event based on the customers cart contents
     *
     * @return void
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

        $data = [
            "items" => []
        ];
        foreach ($items as $item) {
            $data['items'][] = $item;
        }

        return $data;
    }
}
