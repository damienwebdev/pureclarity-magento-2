<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Category;

use Pureclarity\Core\Api\CategoryFeedDataManagementInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
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

    /** @var StoreInterface */
    private $currentStore;

    /** @var Collection */
    private $collection;

    /** @var LoggerInterface */
    private $logger;

    /** @var Error */
    private $feedError;

    /** @var CategoryCollectionFactory */
    private $collectionFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @param LoggerInterface $logger
     * @param Error $feedError
     * @param CategoryCollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LoggerInterface $logger,
        Error $feedError,
        CategoryCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->logger            = $logger;
        $this->feedError         = $feedError;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager      = $storeManager;
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
     * @param int $storeId
     * @return int
     */
    public function getTotalPages(int $storeId): int
    {
        $totalPages = 0;
        try {
            $totalPages = $this->getCategoryCollection($storeId)->getLastPageNumber();
        } catch (NoSuchEntityException | LocalizedException $e) {
            $error = 'Could not load categories: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError($storeId, Feed::FEED_TYPE_CATEGORY, $error);
        }

        return $totalPages;
    }

    /**
     * Loads a page of category data for the feed
     * @param int $storeId
     * @param int $pageNum
     * @return Category[]
     */
    public function getPageData(int $storeId, int $pageNum): array
    {
        $customers = [];
        try {
            $collection = $this->getCategoryCollection($storeId);
            $collection->clear();
            $collection->setCurPage($pageNum);
            $customers = $collection->getItems();
        } catch (NoSuchEntityException | LocalizedException $e) {
            $error = 'Could not load categories: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError($storeId, Feed::FEED_TYPE_CATEGORY, $error);
        }

        return $customers;
    }

    /**
     * Returns the built category collection
     * @param int $storeId
     * @return Collection
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCategoryCollection(int $storeId): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->buildCategoryCollection($storeId);
        }
        return $this->collection;
    }

    /**
     * Builds the collection for category feed
     * @param int $storeId
     * @return Collection
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function buildCategoryCollection(int $storeId): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->setStore($this->getCurrentStore($storeId));
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

    /**
     * Gets a Store object for the given Store ID
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
