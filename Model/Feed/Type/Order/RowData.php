<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Order;

use Magento\Sales\Model\Order;
use Pureclarity\Core\Api\OrderFeedRowDataManagementInterface;

/**
 * Class RowData
 *
 * Handles individual order data rows in the feed
 */
class RowData implements OrderFeedRowDataManagementInterface
{
    /**
     * Builds the order data for the order feed.
     * @param int $storeId
     * @param Order $order
     * @return array
     */
    public function getRowData(int $storeId, $order): array
    {
        $orderData = [];

        $id = $order->getIncrementId();
        $customerId = $order->getCustomerId();
        $email = $order->getCustomerEmail();
        $date = $order->getCreatedAt();

        foreach ($order->getAllVisibleItems() as $item) {
            $orderData[] = [
                'OrderID' => $id,
                'UserId' => $customerId ?: '',
                'Email' => $email,
                'DateTime' => $date,
                'ProdCode' => $item->getProductId(),
                'Quantity' => $item->getQtyOrdered(),
                'UnitPrice' => $item->getPriceInclTax(),
                'LinePrice' => $item->getRowTotalInclTax()
            ];
        }

        return $orderData;
    }
}
