<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Order;

use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\OrderFeedRowDataManagementInterface;

/**
 * Class RowData
 *
 * Handles individual order data rows in the feed
 */
class RowData implements OrderFeedRowDataManagementInterface
{

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }
    /**
     * Builds the order data for the order feed.
     * @param StoreInterface $store
     * @param Order $row
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getRowData(StoreInterface $store, $row): array
    {
        $orderData = [];

        $orderId = $row->getIncrementId();
        $customerId = $row->getCustomerId();
        $email = $row->getCustomerEmail();
        $date = $row->getCreatedAt();

        $this->logger->debug('Order Feed: Processing order ' . $orderId);

        foreach ($row->getAllVisibleItems() as $item) {

            $this->logger->debug('Order Feed: Processing order item ' . $item->getId());

            $orderData[] = [
                'OrderID' => $orderId,
                'UserId' => $customerId ?: '',
                'Email' => $email,
                'DateTime' => $date,
                'ProdCode' => $item->getProductId(),
                'Quantity' => $item->getQtyOrdered(),
                'UnitPrice' => $item->getPriceInclTax(),
                'LinePrice' => $item->getRowTotalInclTax()
            ];
        }

        $this->logger->debug('Order Feed: Order data - ' . var_export($orderData, true));

        return $orderData;
    }
}
