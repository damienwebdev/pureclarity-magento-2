<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Category;

use Pureclarity\Core\Api\CategoryFeedDataManagementInterface;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\Category;
use PureClarity\Api\Feed\Feed;
use Magento\Catalog\Model\ResourceModel\Category\Collection;

/**
 * Class FeedData
 *
 * Handles data gathering for category feed
 */
class FeedData implements CategoryFeedDataManagementInterface
{
    /** @var int */
    private const PAGE_SIZE = 50;

    /** @var Collection */
    private $collection;

    /** @var LoggerInterface */
    private $logger;

    /** @var Error */
    private $feedError;

    /** @var CategoryCollectionFactory */
    private $collectionFactory;

    /**
     * @param LoggerInterface $logger
     * @param Error $feedError
     * @param CategoryCollectionFactory $collectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Error $feedError,
        CategoryCollectionFactory $collectionFactory
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
     * Returns the total number of pages for the category feed
     * @param StoreInterface $store
     * @return int
     */
    public function getTotalPages(StoreInterface $store): int
    {
        $totalPages = 0;
        try {
            $totalPages = $this->getCategoryCollection($store)->getLastPageNumber();
        } catch (LocalizedException $e) {
            $error = 'Could not load categories: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError((int)$store->getId(), Feed::FEED_TYPE_CATEGORY, $error);
        }

        return $totalPages;
    }

    /**
     * Loads a page of category data for the feed
     * @param StoreInterface $store
     * @param int $pageNum
     * @return Category[]
     */
    public function getPageData(StoreInterface $store, int $pageNum): array
    {
        $customers = [];
        try {
            $collection = $this->getCategoryCollection($store);
            $collection->clear();
            $collection->setCurPage($pageNum);
            $customers = $collection->getItems();
        } catch (LocalizedException $e) {
            $error = 'Could not load categories: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError((int)$store->getId(), Feed::FEED_TYPE_CATEGORY, $error);
        }

        return $customers;
    }

    /**
     * Returns the built category collection
     * @param StoreInterface $store
     * @return Collection
     * @throws LocalizedException
     */
    public function getCategoryCollection(StoreInterface $store): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->buildCategoryCollection($store);
        }
        return $this->collection;
    }

    /**
     * Builds the collection for category feed
     * @param StoreInterface $store
     * @return Collection
     * @throws LocalizedException
     */
    public function buildCategoryCollection(StoreInterface $store): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->setStore($store);
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('is_active');
        $collection->addAttributeToSelect('image');
        $collection->addAttributeToSelect('description');
        $collection->addAttributeToSelect('pureclarity_category_image');
        $collection->addAttributeToSelect('pureclarity_hide_from_feed');
        $collection->addUrlRewriteToResult();
        $collection->setPageSize($this->getPageSize());
        return $collection;
    }
}
