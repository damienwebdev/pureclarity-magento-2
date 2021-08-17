<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\User;

use Magento\Customer\Model\Customer;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\Type\User;
use Pureclarity\Core\Model\Feed\Type\User\RowData;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;

/**
 * Class RowDataTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\User\RowData
 */
class RowDataTest extends TestCase
{
    /** @var User */
    private $object;

    /** @var MockObject|CustomerGroupCollection */
    private $customerGroupCollection;

    /** @var MockObject|CustomerGroupCollectionFactory */
    private $customerGroupCollectionFactory;

    /** @var MockObject|LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->customerGroupCollection = $this->createMock(CustomerGroupCollection::class);
        $this->customerGroupCollectionFactory = $this->createMock(CustomerGroupCollectionFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->customerGroupCollectionFactory->method('create')->willReturn($this->customerGroupCollection);

        $this->object = new RowData(
            $this->customerGroupCollectionFactory,
            $this->logger
        );
    }

    /**
     * Builds dummy data for user feed
     * @param int $customerId
     * @return array
     */
    public function mockCustomerData(int $customerId): array
    {
        $data = [
            'UserId' => $customerId,
            'Email' => 'customer' . $customerId . '@example.com',
            'FirstName' => 'FN ' . $customerId,
            'LastName' => 'LN ' . $customerId
        ];

        if ($customerId === 1) {
            $data['Salutation'] = 'Mr';
            $data['DOB'] = '23/03/1980';
            $data['Group'] = 'Group A';
            $data['GroupId'] = 1;
            $data['Gender'] = 'M';
        } else {
            $data['Group'] = 'Group B';
            $data['GroupId'] = 2;
            $data['Gender'] = 'F';
        }

        $data['City'] = 'City ' . $customerId;
        $data['State'] = 'Region-' . $customerId;
        $data['Country'] = 'CID-' . $customerId;

        return $data;
    }

    /**
     * Sets up a customer MockObject
     * @param int $customerId
     * @param array $data
     * @return MockObject|Customer
     */
    public function setupCustomer(int $customerId, array $data)
    {
        $customer = $this->createPartialMock(
            Customer::class,
            [
                'getId',
                '__call',
                'getGroupId',
                'getData'
            ]
        );

        $customer->method('getId')
            ->willReturn($customerId);

        $customer->expects(self::at(2))
            ->method('__call')
            ->with('getEmail')
            ->willReturn($data['Email']);

        $customer->expects(self::at(3))
            ->method('__call')
            ->with('getFirstname')
            ->willReturn($data['FirstName']);

        $customer->expects(self::at(4))
            ->method('__call')
            ->with('getLastname')
            ->willReturn($data['LastName']);

        $customer->expects(self::at(5))
            ->method('__call')
            ->with('getPrefix')
            ->willReturn($data['Salutation'] ?? '');

        $customer->expects(self::at(6))
            ->method('__call')
            ->with('getDob')
            ->willReturn($data['DOB'] ?? '');

        $customer->method('getGroupId')
            ->willReturn($data['GroupId']);

        $customer->expects(self::at(8))
            ->method('__call')
            ->with('getGender')
            ->willReturn($data['GroupId']);

        $customer->expects(self::at(9))
            ->method('getData')
            ->with('city')
            ->willReturn($data['City']);

        $customer->expects(self::at(10))
            ->method('getData')
            ->with('region')
            ->willReturn($data['State']);

        $customer->expects(self::at(11))
            ->method('getData')
            ->with('country_id')
            ->willReturn($data['Country']);

        return $customer;
    }

    /**
     * Sets up customer group data
     */
    public function setupCustomerGroups(): void
    {
        $this->customerGroupCollection->expects(self::once())
            ->method('toOptionArray')
            ->willReturn([
                1 => ['label' => 'Group A'],
                2 => ['label' => 'Group B']
            ]);
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(RowData::class, $this->object);
    }

    /**
     * Tests that a row of data is processed correctly
     */
    public function testGetRowData(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $this->setupCustomerGroups();
        $data = $this->mockCustomerData(1);
        $customer = $this->setupCustomer(1, $data);
        $this->object->getRowData($store, $customer);
    }

    /**
     * Tests that a row of data is processed correctly when data is missing some optional fields
     */
    public function testGetRowDataWithoutOptional(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $this->setupCustomerGroups();
        $data = $this->mockCustomerData(2);
        $customer = $this->setupCustomer(2, $data);
        $this->object->getRowData($store, $customer);
    }
}
