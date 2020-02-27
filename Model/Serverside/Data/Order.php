<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Serverside\Data;

use Magento\Sales\Model\Order as SalesOrder;

/**
 * Serverside Order handler, gets Order contents for sending to a tracking event
 */
class Order
{
    /**
     * Builds an array of data from the provided order class, ready to be sent in the order track event
     *
     * @param SalesOrder $order
     * @return mixed[]
     */
    public function buildOrderTrack($order)
    {
        $orderData = [
            'orderid' => $order['increment_id'],
            'firstname' => $order['customer_firstname'],
            'lastname' => $order['customer_lastname'],
            'email' => $order['customer_email'],
            'postcode' => $order->getShippingAddress()['postcode'],
            'userid' => $order['customer_id'],
            'groupid' => $order['customer_group_id'],
            'ordertotal' => $order['grand_total']
        ];

        $orderItems = [];
        $visibleItems = $order->getAllVisibleItems();
        $allItems = $order->getAllItems();
        $count = 0;

        foreach ($visibleItems as $item) {
            $count++;

            $orderItems[$item->getItemId()] = [
                'id' . $count => $item->getProductId(),
                'refid' . $count => $item->getItemId(),
                'qty' . $count => $item->getQtyOrdered(),
                'unitprice' . $count => $item->getPrice(),
                'children' . $count => []
            ];

            foreach ($allItems as $childItem) {
                $parentId = $childItem->getParentItemId();
                if ($parentId && isset($orderItems[$parentId])) {
                    $orderItems[$parentId]['children' . $count][] = [
                        'sku' => $childItem->getSku(),
                        'qty' => $childItem->getQtyOrdered()
                    ];
                }
            }
        }

        $orderData['productcount'] = $count;

        foreach ($orderItems as $item) {
            foreach ($item as $key => $value) {
                $orderData[$key] = $value;
            }
        }

        return $orderData;
    }
}
