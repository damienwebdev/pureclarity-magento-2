<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type;

use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\CoreConfig;
use PureClarity\Api\Feed\Type\UserFactory;
use Pureclarity\Core\Model\Feed\Progress;
use Pureclarity\Core\Model\Feed\RunDate;
use Pureclarity\Core\Model\Feed\Error;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class User
 *
 * Handles running of user feed
 */
class User
{
    /** @var array */
    private $customerGroups;

    /** @var StoreInterface */
    private $currentStore;

    /** @var LoggerInterface */
    private $logger;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var UserFactory */
    private $userFeedFactory;

    /** @var Progress */
    private $feedProgress;

    /** @var RunDate */
    private $feedRunDate;

    /** @var Error */
    private $feedError;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CustomerCollectionFactory */
    private $customerCollectionFactory;

    /** @var CustomerGroupCollectionFactory */
    private $customerGroupCollectionFactory;

    /**
     * @param LoggerInterface $logger
     * @param CoreConfig $coreConfig
     * @param UserFactory $userFeedFactory
     * @param Progress $feedProgress
     * @param RunDate $feedRunDate
     * @param Error $feedError
     * @param StoreManagerInterface $storeManager
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        CoreConfig $coreConfig,
        UserFactory $userFeedFactory,
        Progress $feedProgress,
        RunDate $feedRunDate,
        Error $feedError,
        StoreManagerInterface $storeManager,
        CustomerCollectionFactory $customerCollectionFactory,
        CustomerGroupCollectionFactory $customerGroupCollectionFactory
    ) {
        $this->logger                         = $logger;
        $this->coreConfig                     = $coreConfig;
        $this->userFeedFactory                = $userFeedFactory;
        $this->feedProgress                   = $feedProgress;
        $this->feedRunDate                    = $feedRunDate;
        $this->feedError                      = $feedError;
        $this->customerCollectionFactory      = $customerCollectionFactory;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->storeManager                   = $storeManager;
    }

    /**
     * Builds & sends the user feed
     * @param int $storeId
     * @return void
     */
    public function send(int $storeId) : void
    {
        $customers = $this->getCustomers($storeId);

        if ($customers) {
            $this->feedProgress->updateProgress($storeId, 'user', '0');

            try {
                $userFeed = $this->userFeedFactory->create([
                    'accessKey' => $this->coreConfig->getAccessKey($storeId),
                    'secretKey' => $this->coreConfig->getSecretKey($storeId),
                    'region' => $this->coreConfig->getRegion($storeId)
                ]);

                $userFeed->start();

                $current = 1;
                $total = count($customers);
                foreach ($customers as $customer) {
                    $userFeed->append($this->getCustomerData($customer));
                    if (($current % 50) === 0) {
                        $this->feedProgress->updateProgress(
                            $storeId,
                            'user',
                            (string)round(($current / $total) * 100)
                        );
                    }

                    $current++;
                }

                $userFeed->end();
            } catch (\Exception $e) {
                $this->logger->error('PureClarity: Error with user feed: ' . $e->getMessage());
                $this->feedError->saveFeedError($storeId, 'user', $e->getMessage());
            }

            $this->feedProgress->updateProgress($storeId, 'user', '100');
            $this->feedRunDate->setLastRunDate($storeId, 'user', date('Y-m-d H:i:s'));
        }
    }

    /**
     * Loads customer data for the feed
     * @param int $storeId
     * @return Customer[]
     */
    public function getCustomers(int $storeId): array
    {
        $customers = [];
        try {
            $customers = $this->getCustomerCollection($storeId)->getItems();
        } catch (LocalizedException | NoSuchEntityException $e) {
            $error = 'Could not load users: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError($storeId, 'user', $error);
        }

        return $customers;
    }

    /**
     * Builds the customer collection for user feed, includes default shipping / first address found
     * @param int $storeId
     * @return Collection
     * @throws LocalizedException
     */
    public function getCustomerCollection(int $storeId): Collection
    {
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addAttributeToFilter(
            'website_id',
            [ "eq" => $this->getCurrentStore($storeId)->getWebsiteId()]
        );

        $table = $customerCollection->getTable('customer_address_entity');
        $customerCollection->joinTable(
            ['cad' => $table],
            'parent_id = entity_id',
            ['city', 'region', 'country_id'],
            '`cad`.entity_id=`e`.default_shipping OR cad.parent_id = e.entity_id',
            'left'
        );
        $customerCollection->groupByAttribute('entity_id');

        return $customerCollection;
    }

    /**
     * Builds the customer data for the user feed.
     * @param Customer $customer
     * @return array
     */
    public function getCustomerData(Customer $customer): array
    {
        $customerGroups = $this->getCustomerGroups();
        $data = [
            'UserId' => $customer->getId(),
            'Email' => $customer->getEmail(),
            'FirstName' => $customer->getFirstname(),
            'LastName' => $customer->getLastname()
        ];
        if ($customer->getPrefix()) {
            $data['Salutation'] = $customer->getPrefix();
        }
        if ($customer->getDob()) {
            $data['DOB'] = $customer->getDob();
        }
        if ($customer->getGroupId() && $customerGroups[$customer->getGroupId()]) {
            $data['Group'] = $customerGroups[$customer->getGroupId()]['label'];
            $data['GroupId'] = $customer->getGroupId();
        }
        if ($customer->getGender()) {
            switch ($customer->getGender()) {
                case 1: // Male
                    $data['Gender'] = 'M';
                    break;
                case 2: // Female
                    $data['Gender'] = 'F';
                    break;
            }
        }

        $data['City'] = $customer->getData('city');
        $data['State'] = $customer->getData('region');
        $data['Country'] = $customer->getData('country_id');

        return $data;
    }

    /**
     * Loads all customer groups in the system
     * @return array
     */
    public function getCustomerGroups(): array
    {
        if ($this->customerGroups === null) {
            $customerGroupCollection = $this->customerGroupCollectionFactory->create();
            $this->customerGroups = $customerGroupCollection->toOptionArray();
        }

        return $this->customerGroups;
    }

    /**
     * Gets a Store object for the given Store
     * @param int $storeId
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getCurrentStore(int $storeId): StoreInterface
    {
        if (empty($this->currentStore)) {
            $this->currentStore = $this->storeManager->getStore($storeId);
        }
        return $this->currentStore;
    }
}
