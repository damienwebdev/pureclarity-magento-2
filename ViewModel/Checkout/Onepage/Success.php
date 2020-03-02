<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Checkout\Onepage;

use Magento\Checkout\Model\Session;
use Pureclarity\Core\Helper\Serializer;

class Success
{
    /** @var Session */
    private $checkoutSession;

    /** @var Serializer */
    private $serializer;

    /**
     * @param Session $checkoutSession
     * @param Serializer $serializer
     */
    public function __construct(
        Session $checkoutSession,
        Serializer $serializer
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->serializer      = $serializer;
    }

    /**
     * @return string
     */
    public function getOrderJson()
    {
        $order = [];
        $lastOrder = $this->checkoutSession ->getLastRealOrder();

        if ($lastOrder) {
            $order = [
                'orderid' => $lastOrder['increment_id'],
                'firstname' => $lastOrder['customer_firstname'],
                'lastname' => $lastOrder['customer_lastname'],
                'postcode' => $lastOrder->getShippingAddress()['postcode'],
                'userid' => $lastOrder['customer_id'],
                'groupid' => $lastOrder['customer_group_id'],
                'ordertotal' => $lastOrder['grand_total'],
                'email' => $lastOrder['customer_email']
            ];

            $orderItems = [];
            $visibleItems = $lastOrder->getAllVisibleItems();
            $allItems = $lastOrder->getAllItems();
            foreach ($visibleItems as $item) {
                $orderItems[$item->getItemId()] = [
                    'orderid' => $lastOrder['increment_id'],
                    'refid' => $item->getItemId(),
                    'id' => $item->getProductId(),
                    'qty' => $item->getQtyOrdered(),
                    'unitprice' => $item->getPrice(),
                    'children' => []
                ];
            }

            foreach ($allItems as $item) {
                if ($item->getParentItemId() && $orderItems[$item->getParentItemId()]) {
                    $orderItems[$item->getParentItemId()]['children'][] = [
                        'sku' => $item->getSku(),
                        'qty' => $item->getQtyOrdered()
                    ];
                }
            }

            $order['items'] = array_values($orderItems);
        }

        return $this->serializer->serialize($order);
    }
}
