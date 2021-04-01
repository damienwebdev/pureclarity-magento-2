<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Order;

use Magento\Store\Api\Data\StoreInterface;
use Pureclarity\Core\Api\OrderFeedDataManagementInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use PureClarity\Api\Feed\Feed;
use Magento\Sales\Model\Order;

/**
 * Class FeedData
 *
 * Handles data gathering for order feed
 */
class FeedData implements OrderFeedDataManagementInterface
{
    /** @var int */
    private const PAGE_SIZE = 50;

    /** @var Collection */
    private $collection;

    /** @var LoggerInterface */
    private $logger;

    /** @var Error */
    private $feedError;

    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * @param LoggerInterface $logger
     * @param Error $feedError
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Error $feedError,
        CollectionFactory $collectionFactory
    ) {
        $this->logger            = $logger;
        $this->feedError         = $feedError;
        $this->collectionFactory = $collectionFactory;
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
     * Returns the total number of pages for the order feed
     * @param StoreInterface $store
     * @return int
     */
    public function getTotalPages(StoreInterface $store): int
    {
        $totalPages = 0;
        try {
            $totalPages = $this->getOrderCollection($store)->getLastPageNumber();
        } catch (LocalizedException $e) {
            $error = 'Could not load orders: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError((int)$store->getId(), Feed::FEED_TYPE_ORDER, $error);
        }

        return $totalPages;
    }

    /**
     * Loads a page of customer data for the feed
     * @param StoreInterface $store
     * @param int $pageNum
     * @return Order[]
     */
    public function getPageData(StoreInterface $store, int $pageNum): array
    {
        $customers = [];
        try {
            $collection = $this->getOrderCollection($store);
            $collection->clear();
            $collection->setCurPage($pageNum);
            $customers = $collection->getItems();
        } catch (LocalizedException $e) {
            $error = 'Could not load orders: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError((int)$store->getId(), Feed::FEED_TYPE_ORDER, $error);
        }

        return $customers;
    }

    /**
     * Returns the built order collection
     * @param StoreInterface $store
     * @return Collection
     * @throws LocalizedException
     */
    public function getOrderCollection(StoreInterface $store): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->buildOrderCollection($store);
        }
        return $this->collection;
    }

    /**
     * Builds the order collection for order feed
     * @param StoreInterface $store
     * @return Collection
     * @throws LocalizedException
     */
    public function buildOrderCollection(StoreInterface $store): Collection
    {
        $fromDate = date('Y-m-d H:i:s', strtotime("-12 month"));
        $toDate = date('Y-m-d H:i:s');

        $collection = $this->collectionFactory->create();
        $collection->addAttributeToFilter('store_id', (int)$store->getId());
        $collection->addAttributeToFilter('created_at', ['from'=> $fromDate, 'to'=> $toDate]);
        $collection->setPageSize($this->getPageSize());

        return $collection;
    }
}
