<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\User;

use Pureclarity\Core\Api\UserFeedDataManagementInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use PureClarity\Api\Feed\Feed;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Customer;

/**
 * Class FeedData
 *
 * Handles data gathering for user feed
 */
class FeedData implements UserFeedDataManagementInterface
{
    /** @var int */
    private const PAGE_SIZE = 50;

    /** @var Collection */
    private $collection;

    /** @var LoggerInterface */
    private $logger;

    /** @var Error */
    private $feedError;

    /** @var CustomerCollectionFactory */
    private $customerCollectionFactory;

    /**
     * @param LoggerInterface $logger
     * @param Error $feedError
     * @param CustomerCollectionFactory $customerCollectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Error $feedError,
        CustomerCollectionFactory $customerCollectionFactory
    ) {
        $this->logger                         = $logger;
        $this->feedError                      = $feedError;
        $this->customerCollectionFactory      = $customerCollectionFactory;
    }

    /**
     * Gets the page size of the feed
     * @return int
     */
    public function getPageSize(): int
    {
        return self::PAGE_SIZE;
    }

    /**
     * Returns the total number of pages for the user feed
     * @param StoreInterface $store
     * @return int
     */
    public function getTotalPages(StoreInterface $store): int
    {
        $totalPages = 0;
        try {
            $totalPages = $this->getCustomerCollection($store)->getLastPageNumber();
        } catch (LocalizedException $e) {
            $error = 'Could not load users: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError((int)$store->getId(), Feed::FEED_TYPE_USER, $error);
        }

        return $totalPages;
    }

    /**
     * Loads a page of customer data for the feed
     * @param StoreInterface $store
     * @param int $pageNum
     * @return Customer[]
     */
    public function getPageData(StoreInterface $store, int $pageNum): array
    {
        $customers = [];
        try {
            $collection = $this->getCustomerCollection($store);
            $collection->clear();
            $collection->setCurPage($pageNum);
            $customers = $collection->getItems();
        } catch (LocalizedException $e) {
            $error = 'Could not load users: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError((int)$store->getId(), Feed::FEED_TYPE_USER, $error);
        }

        return $customers;
    }

    /**
     * Returns the build customer collection
     * @param StoreInterface $store
     * @return Collection
     * @throws LocalizedException
     */
    public function getCustomerCollection(StoreInterface $store): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->buildCustomerCollection($store);
        }
        return $this->collection;
    }

    /**
     * Builds the customer collection for user feed, includes default shipping / first address found
     * @param StoreInterface $store
     * @return Collection
     * @throws LocalizedException
     */
    public function buildCustomerCollection(StoreInterface $store): Collection
    {
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToFilter(
            'website_id',
            [ "eq" => $store->getWebsiteId()]
        );

        $table = $collection->getTable('customer_address_entity');
        $collection->joinTable(
            ['cad' => $table],
            'parent_id = entity_id',
            ['city', 'region', 'country_id'],
            '`cad`.entity_id=`e`.default_shipping OR cad.parent_id = e.entity_id',
            'left'
        );
        $collection->groupByAttribute('entity_id');
        $collection->setPageSize($this->getPageSize());
        return $collection;
    }
}
