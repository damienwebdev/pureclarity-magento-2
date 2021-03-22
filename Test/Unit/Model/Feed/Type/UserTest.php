<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type;

use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\User;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\CoreConfig;
use PureClarity\Api\Feed\Type\User as UserFeed;
use PureClarity\Api\Feed\Type\UserFactory;
use Pureclarity\Core\Model\Feed\RunDate;
use Pureclarity\Core\Model\Feed\Error;
use Pureclarity\Core\Model\Feed\Progress;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;

/**
 * Class UserTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\User
 */
class UserTest extends TestCase
{
    /** @var string */
    private const CONFIG_ACCESS_KEY = 'AccessKey1';
    /** @var string */
    private const CONFIG_SECRET_KEY = 'SecretKey123';
    /** @var string */
    private const CONFIG_REGION = '1';
    /** @var int */
    private const STORE_ID = 1;

    /** @var User */
    private $object;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|UserFeed */
    private $userFeed;

    /** @var MockObject|UserFactory */
    private $userFeedFactory;

    /** @var MockObject|Progress */
    private $feedProgress;

    /** @var MockObject|RunDate */
    private $feedRunDate;

    /** @var MockObject|Error */
    private $feedError;

    /** @var MockObject|StoreManagerInterface */
    private $storeManager;

    /** @var MockObject|CustomerCollection */
    private $customerCollection;

    /** @var MockObject|CustomerCollectionFactory */
    private $customerCollectionFactory;

    /** @var MockObject|CustomerGroupCollection */
    private $customerGroupCollection;

    /** @var MockObject|CustomerGroupCollectionFactory */
    private $customerGroupCollectionFactory;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userFeed = $this->getMockBuilder(UserFeed::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userFeedFactory = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userFeedFactory->method('create')->willReturn($this->userFeed);

        $this->feedProgress = $this->getMockBuilder(Progress::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedRunDate = $this->getMockBuilder(RunDate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedError = $this->getMockBuilder(Error::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerCollection = $this->getMockBuilder(CustomerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerCollectionFactory = $this->getMockBuilder(CustomerCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerCollectionFactory->method('create')->willReturn($this->customerCollection);

        $this->customerGroupCollection = $this->getMockBuilder(CustomerGroupCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerGroupCollectionFactory = $this->getMockBuilder(CustomerGroupCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerGroupCollectionFactory->method('create')->willReturn($this->customerGroupCollection);

        $this->object = new User(
            $this->logger,
            $this->coreConfig,
            $this->userFeedFactory,
            $this->feedProgress,
            $this->feedRunDate,
            $this->feedError,
            $this->storeManager,
            $this->customerCollectionFactory,
            $this->customerGroupCollectionFactory
        );
    }

    /**
     * Sets up a StoreInterface and store manager getStore
     * @param bool $error
     */
    public function setupStore(bool $error = false): void
    {
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store->method('getId')
            ->willReturn('1');

        $store->method('getWebsiteId')
            ->willReturn('12');

        if ($error) {
            $this->storeManager->expects(self::once())
                ->method('getStore')
                ->with(self::STORE_ID)
                ->willThrowException(
                    new NoSuchEntityException(new Phrase('An Error'))
                );
        } else {
            $this->storeManager->expects(self::once())
                ->method('getStore')
                ->with(self::STORE_ID)
                ->willReturn($store);
        }
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
     * @return MockObject
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
     * Sets up customer array
     * @param array $customerIds
     * @param bool $error
     * @param bool $feedError
     */
    public function setupCustomers(array $customerIds, bool $error = false, bool $feedError = false): void
    {
        if ($error) {
            $this->customerCollection->expects(self::once())
                ->method('addAttributeToFilter')
                ->with('website_id', ['eq' => 12])
                ->willThrowException(new LocalizedException(new Phrase('An Attribute Error')));
        } elseif ($feedError) {
            $this->customerCollection->expects(self::once())
                ->method('getItems')
                ->willReturn([1]);
        } else {
            $this->customerCollection->expects(self::once())
                ->method('addAttributeToFilter')
                ->with('website_id', ['eq' => 12]);

            $this->customerCollection->expects(self::once())
                ->method('getTable')
                ->willReturn('customer_address_entity');

            $this->customerCollection->expects(self::once())
                ->method('joinTable')
                ->with(
                    ['cad' => 'customer_address_entity'],
                    'parent_id = entity_id',
                    ['city', 'region', 'country_id'],
                    '`cad`.entity_id=`e`.default_shipping OR cad.parent_id = e.entity_id',
                    'left'
                );

            $this->customerCollection->expects(self::once())
                ->method('groupByAttribute')
                ->with('entity_id');

            $customers = [];
            $index = 1;
            foreach ($customerIds as $customerId) {
                $data = $this->mockCustomerData($customerId);
                $customers[$customerId] = $this->setupCustomer($customerId, $data);

                $this->userFeed->expects(self::at($index))
                    ->method('append')
                    ->with($data);

                $index++;
            }

            $this->customerCollection->expects(self::once())
                ->method('getItems')
                ->willReturn($customers);
        }
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
     * Sets up config value mocks
     */
    public function setupConfig(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('getAccessKey')
            ->willReturn(self::CONFIG_ACCESS_KEY);

        $this->coreConfig->expects(self::once())
            ->method('getSecretKey')
            ->willReturn(self::CONFIG_SECRET_KEY);

        $this->coreConfig->expects(self::once())
            ->method('getRegion')
            ->willReturn(self::CONFIG_REGION);
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(User::class, $this->object);
    }

    /**
     * Tests that the user feed doesnt send when no users present
     */
    public function testSendNoUsers(): void
    {
        $this->setupStore();
        $this->setupCustomers([]);

        $this->userFeedFactory->expects(self::never())
            ->method('create');

        $this->object->send(1);
    }

    /**
     * Tests that the user feed gets sent with users present
     */
    public function testSendWithUsers(): void
    {
        $this->setupStore();
        $this->setupConfig();
        $this->setupCustomerGroups();
        $this->setupCustomers([1, 2]);

        $this->userFeedFactory->expects(self::once())
            ->method('create')
            ->with([
                'accessKey' => self::CONFIG_ACCESS_KEY,
                'secretKey' => self::CONFIG_SECRET_KEY,
                'region' => self::CONFIG_REGION
            ]);

        $this->feedProgress->expects(self::at(0))
            ->method('updateProgress')
            ->with(self::STORE_ID, 'user', '0');

        $this->userFeed->expects(self::at(0))
            ->method('start');

        $this->feedProgress->expects(self::at(1))
            ->method('updateProgress')
            ->with(self::STORE_ID, 'user', '100');

        $this->feedRunDate->expects(self::once())
            ->method('setLastRunDate')
            ->with(self::STORE_ID, 'user');

        $this->userFeed->expects(self::at(3))
            ->method('end');

        $this->object->send(1);
    }

    /**
     * Tests with 75 customers so that we can validate progress gets updated at 50 customers
     */
    public function testSendWithUsersValidateProgress(): void
    {
        $this->setupStore();
        $this->setupConfig();
        $this->setupCustomerGroups();

        $ids = [];
        for ($i=1; $i<=75; $i++) {
            $ids[] = $i;
        }

        $this->setupCustomers($ids);

        $this->userFeedFactory->expects(self::once())
            ->method('create')
            ->with([
                'accessKey' => self::CONFIG_ACCESS_KEY,
                'secretKey' => self::CONFIG_SECRET_KEY,
                'region' => self::CONFIG_REGION
            ]);

        $this->feedProgress->expects(self::at(0))
            ->method('updateProgress')
            ->with(self::STORE_ID, 'user', '0');

        $this->userFeed->expects(self::at(0))
            ->method('start');

        $this->feedProgress->expects(self::at(1))
            ->method('updateProgress')
            ->with(self::STORE_ID, 'user', '67');

        $this->feedProgress->expects(self::at(2))
            ->method('updateProgress')
            ->with(self::STORE_ID, 'user', '100');

        $this->feedRunDate->expects(self::once())
            ->method('setLastRunDate')
            ->with(self::STORE_ID, 'user');

        $this->userFeed->expects(self::at(3))
            ->method('end');

        $this->object->send(1);
    }

    /**
     * Tests that a store load exception is handled
     */
    public function testSendStoreException(): void
    {
        $this->setupStore(true);

        $this->userFeedFactory->expects(self::never())
            ->method('create');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load users: An Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'user', 'Could not load users: An Error');

        $this->object->send(1);
    }

    /**
     * Tests that a customer collection exception is handled
     */
    public function testSendCollectionException(): void
    {
        $this->setupStore();
        $this->setupCustomers([], true);

        $this->userFeedFactory->expects(self::never())
            ->method('create');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Could not load users: An Attribute Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'user', 'Could not load users: An Attribute Error');

        $this->object->send(1);
    }

    /**
     * Tests that a user feed exception is handled
     */
    public function testSendFeedException(): void
    {
        $this->setupStore();
        $this->setupCustomers([1], false, true);

        $this->userFeed->expects(self::at(0))
            ->method('start')
            ->willThrowException(new \Exception('A Feed Error'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity: Error with user feed: A Feed Error');

        $this->feedError->expects(self::once())
            ->method('saveFeedError')
            ->with(self::STORE_ID, 'user', 'A Feed Error');

        $this->userFeed->expects(self::never())->method('end');

        $this->object->send(1);
    }
}
