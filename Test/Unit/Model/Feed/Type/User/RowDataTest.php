<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\User;

use Magento\Customer\Model\Customer;
use PHPUnit\Framework\TestCase;
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

    protected function setUp(): void
    {
        $this->customerGroupCollection = $this->getMockBuilder(CustomerGroupCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerGroupCollectionFactory = $this->getMockBuilder(CustomerGroupCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerGroupCollectionFactory->method('create')->willReturn($this->customerGroupCollection);

        $this->object = new RowData(
            $this->customerGroupCollectionFactory
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
    public function setupCustomer(int $customerId, array $data): MockObject
    {
        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId',
                'getEmail',
                'getFirstname',
                'getLastname',
                'getPrefix',
                'getDob',
                'getGroupId',
                'getGender',
                'getData'
            ])
            ->getMock();

        $customer->method('getId')
            ->willReturn($customerId);

        $customer->method('getEmail')
            ->willReturn($data['Email']);

        $customer->method('getFirstname')
            ->willReturn($data['FirstName']);

        $customer->method('getLastname')
            ->willReturn($data['LastName']);

        $customer->method('getPrefix')
            ->willReturn($data['Salutation'] ?? '');

        $customer->method('getDob')
            ->willReturn($data['DOB'] ?? '');

        $customer->method('getGroupId')
            ->willReturn($data['GroupId']);

        $customer->method('getGender')
            ->willReturn($data['GroupId']);

        $customer->expects(self::at(8))
            ->method('getData')
            ->with('city')
            ->willReturn($data['City']);

        $customer->expects(self::at(9))
            ->method('getData')
            ->with('region')
            ->willReturn($data['State']);

        $customer->expects(self::at(10))
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
        $this->setupCustomerGroups();
        $data = $this->mockCustomerData(1);
        $customer = $this->setupCustomer(1, $data);
        $this->object->getRowData(1, $customer);
    }

    /**
     * Tests that a row of data is processed correctly when data is missing some optional fields
     */
    public function testGetRowDataWithoutOptional(): void
    {
        $this->setupCustomerGroups();
        $data = $this->mockCustomerData(2);
        $customer = $this->setupCustomer(2, $data);
        $this->object->getRowData(1, $customer);
    }
}
