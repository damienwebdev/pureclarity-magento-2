<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Product;

use Magento\Store\Api\Data\StoreInterface;
use Pureclarity\Core\Api\ProductFeedDataManagementInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use PureClarity\Api\Feed\Feed;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Class FeedData
 *
 * Handles data gathering for product feed
 */
class FeedData implements ProductFeedDataManagementInterface
{
    /** @var int */
    private const PAGE_SIZE = 50;

    /** @var Collection */
    private $collection;

    /** @var LoggerInterface */
    private $logger;

    /** @var Error */
    private $feedError;

    /** @var ProductCollectionFactory */
    private $collectionFactory;

    /**
     * @param LoggerInterface $logger
     * @param Error $feedError
     * @param ProductCollectionFactory $collectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Error $feedError,
        ProductCollectionFactory $collectionFactory
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
     * Returns the total number of pages for the product feed
     * @param StoreInterface $store
     * @return int
     */
    public function getTotalPages(StoreInterface $store): int
    {
        $totalPages = 0;
        try {
            $totalPages = (int)$this->getCollection($store)->getLastPageNumber();
        } catch (LocalizedException $e) {
            $error = 'Could not load products: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError((int)$store->getId(), Feed::FEED_TYPE_PRODUCT, $error);
        }

        return $totalPages;
    }

    /**
     * Loads a page of product data for the feed
     * @param StoreInterface $store
     * @param int $pageNum
     * @return array []
     */
    public function getPageData(StoreInterface $store, int $pageNum): array
    {
        $customers = [];
        try {
            $collection = $this->getCollection($store);
            $collection->clear();
            $collection->setCurPage($pageNum);
            $customers = $collection->getItems();
        } catch (LocalizedException $e) {
            $error = 'Could not load products: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError((int)$store->getId(), Feed::FEED_TYPE_PRODUCT, $error);
        }

        return $customers;
    }

    /**
     * Returns the built collection
     * @param StoreInterface $store
     * @return Collection
     * @throws LocalizedException
     */
    public function getCollection(StoreInterface $store): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->buildCollection($store);
        }
        return $this->collection;
    }

    /**
     * Builds the collection for product feed
     * @param StoreInterface $store
     * @return Collection
     * @throws LocalizedException - thrown by addAttributeToSelect / addFieldToFilter
     */
    public function buildCollection(StoreInterface $store): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->setStoreId((int)$store->getId());
        $collection->addStoreFilter($store);
        $collection->addUrlRewrite();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter("status", ["eq" => Status::STATUS_ENABLED]);
        $collection->addFieldToFilter('visibility', [
            'in' => [
                Visibility::VISIBILITY_BOTH,
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH
            ]
        ]);
        $collection->addMinimalPrice();
        $collection->addTaxPercents();
        $collection->setPageSize($this->getPageSize());

        return $collection;
    }
}
