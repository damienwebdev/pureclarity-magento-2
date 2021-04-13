<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Order;

use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Order\RowData;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;

/**
 * Class RowDataTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Order\RowData
 */
class RowDataTest extends TestCase
{
    /** @var RowData */
    private $object;

    protected function setUp(): void
    {
        $this->object = new RowData();
    }

    /**
     * Builds dummy data for order feed - single line order
     * @return array
     */
    public function mockSingleLineOrder(): array
    {
        return [
            [
                'OrderID' => '000000009',
                'UserId' => '1',
                'Email' => 'user1@example.com',
                'DateTime' => '2021-01-01 13:49:24',
                'ProdCode' => '1',
                'Quantity' => '2',
                'UnitPrice' => '10.0000',
                'LinePrice' => '20.0000'
            ]
        ];
    }

    /**
     * Builds dummy data for order feed - single line order
     * @return array
     */
    public function mockGuestOrder(): array
    {
        return [
            [
                'OrderID' => '000000010',
                'UserId' => '',
                'Email' => 'user@example.com',
                'DateTime' => '2021-01-01 13:49:24',
                'ProdCode' => '1',
                'Quantity' => '2',
                'UnitPrice' => '10.0000',
                'LinePrice' => '20.0000'
            ]
        ];
    }

    /**
     * Builds dummy data for order feed - multi line order
     * @return array
     */
    public function mockMultiLineOrder(): array
    {
        return [
            [
                'OrderID' => '000000011',
                'UserId' => '2',
                'Email' => 'user2@example.com',
                'DateTime' => '2021-01-01 13:49:24',
                'ProdCode' => '12',
                'Quantity' => '1',
                'UnitPrice' => '10.0000',
                'LinePrice' => '10.0000'
            ],
            [
                'OrderID' => '000000011',
                'UserId' => '2',
                'Email' => 'user2@example.com',
                'DateTime' => '2021-01-01 13:49:24',
                'ProdCode' => '13',
                'Quantity' => '2',
                'UnitPrice' => '10.0000',
                'LinePrice' => '20.0000'
            ]
        ];
    }

    /**
     * Sets up a order MockObject
     * @param string $orderId
     * @param string $customerId
     * @param array $items
     * @return MockObject|Order
     */
    public function setupOrder(string $orderId, string $customerId, array $items)
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getIncrementId',
                'getCustomerId',
                'getCustomerEmail',
                'getCreatedAt',
                'getAllVisibleItems'
            ])
            ->getMock();

        $order->method('getIncrementId')
            ->willReturn($orderId);

        $order->method('getCustomerId')
            ->willReturn($customerId);

        $order->method('getCustomerEmail')
            ->willReturn('user' . $customerId . '@example.com');

        $order->method('getCreatedAt')
            ->willReturn('2021-01-01 13:49:24');

        $order->method('getAllVisibleItems')
            ->willReturn($this->generateItems($items));
        return $order;
    }

    /**
     * Sets up order items MockObjects
     * @param array $items
     * @return array
     */
    public function generateItems(array $items): array
    {
        $itemMocks = [];
        foreach ($items as $item) {
            $itemMock = $this->getMockBuilder(Item::class)
                ->disableOriginalConstructor()
                ->setMethods([
                    'getProductId',
                    'getQtyOrdered',
                    'getPriceInclTax',
                    'getRowTotalInclTax'
                ])
                ->getMock();

            $itemMock->method('getProductId')
                ->willReturn($item['id']);

            $itemMock->method('getQtyOrdered')
                ->willReturn($item['qty']);

            $itemMock->method('getPriceInclTax')
                ->willReturn($item['price']);

            $itemMock->method('getRowTotalInclTax')
                ->willReturn($item['row_price']);

            $itemMocks[] = $itemMock;
        }

        return $itemMocks;
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(RowData::class, $this->object);
    }

    /**
     * Tests that a single line order is processed correctly
     */
    public function testSingleLineOrder(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $items = [[
            'id' => '1',
            'qty' => '2',
            'price' => '10.0000',
            'row_price' => '20.0000'
        ]];
        $data = $this->mockSingleLineOrder();
        $order = $this->setupOrder('000000009', '1', $items);
        $rowData = $this->object->getRowData($store, $order);
        self::assertEquals($data, $rowData);
    }

    /**
     * Tests that a guest order is processed correctly
     */
    public function testGuestOrder(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $items = [[
            'id' => '1',
            'qty' => '2',
            'price' => '10.0000',
            'row_price' => '20.0000'
        ]];
        $data = $this->mockGuestOrder();
        $order = $this->setupOrder('000000010', '', $items);
        $rowData = $this->object->getRowData($store, $order);
        self::assertEquals($data, $rowData);
    }

    /**
     * Tests that a multi line order is processed correctly
     */
    public function testMultiLineOrder(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $items = [
            [
                'id' => '12',
                'qty' => '1',
                'price' => '10.0000',
                'row_price' => '10.0000'
            ],
            [
                'id' => '13',
                'qty' => '2',
                'price' => '10.0000',
                'row_price' => '20.0000'
            ]
        ];
        $data = $this->mockMultiLineOrder();
        $order = $this->setupOrder('000000011', '2', $items);
        $rowData = $this->object->getRowData($store, $order);
        self::assertEquals($data, $rowData);
    }
}
