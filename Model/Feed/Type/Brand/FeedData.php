<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Brand;

use Magento\Store\Api\Data\StoreInterface;
use Pureclarity\Core\Api\BrandFeedDataManagementInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Feed\State\Error;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\CategoryRepository;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Model\Category;
use PureClarity\Api\Feed\Feed;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class FeedData
 *
 * Handles data gathering for brand feed
 */
class FeedData implements BrandFeedDataManagementInterface
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

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var CoreConfig */
    private $coreConfig;

    /**
     * @param LoggerInterface $logger
     * @param Error $feedError
     * @param CategoryCollectionFactory $collectionFactory
     * @param CategoryRepository $categoryRepository
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        LoggerInterface $logger,
        Error $feedError,
        CategoryCollectionFactory $collectionFactory,
        CategoryRepository $categoryRepository,
        CoreConfig $coreConfig
    ) {
        $this->logger             = $logger;
        $this->feedError          = $feedError;
        $this->collectionFactory  = $collectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->coreConfig         = $coreConfig;
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
     * Returns the total number of pages for the brand feed
     * @param StoreInterface $store
     * @return int
     */
    public function getTotalPages(StoreInterface $store): int
    {
        $totalPages = 0;
        try {
            $totalPages = (int)$this->getBrandCollection($store)->getLastPageNumber();
        } catch (LocalizedException $e) {
            $error = 'Could not load brands: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError((int)$store->getId(), Feed::FEED_TYPE_BRAND, $error);
        }

        return $totalPages;
    }

    /**
     * Loads a page of brand data for the feed
     * @param StoreInterface $store
     * @param int $pageNum
     * @return Category[]
     */
    public function getPageData(StoreInterface $store, int $pageNum): array
    {
        $customers = [];
        try {
            $collection = $this->getBrandCollection($store);
            $collection->clear();
            $collection->setCurPage($pageNum);
            $customers = $collection->getItems();
        } catch (LocalizedException $e) {
            $error = 'Could not load brands: ' . $e->getMessage();
            $this->logger->error('PureClarity: ' . $error);
            $this->feedError->saveFeedError((int)$store->getId(), Feed::FEED_TYPE_BRAND, $error);
        }

        return $customers;
    }

    /**
     * Returns the built brand collection
     * @param StoreInterface $store
     * @return Collection
     * @throws LocalizedException
     */
    public function getBrandCollection(StoreInterface $store): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->buildBrandCollection($store);
        }
        return $this->collection;
    }

    /**
     * Builds the collection for brand feed
     * @param StoreInterface $store
     * @return Collection
     * @throws LocalizedException
     */
    public function buildBrandCollection(StoreInterface $store): Collection
    {
        $brandCategoryId = $this->coreConfig->getBrandParentCategory((int)$store->getId());
        $brandParentCategory = $this->categoryRepository->get($brandCategoryId);
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('image');
        $collection->addAttributeToSelect('description');
        $collection->addAttributeToSelect('pureclarity_category_image');
        $collection->addAttributeToSelect('pureclarity_hide_from_feed');
        $collection->addIdFilter($brandParentCategory->getChildren());
        $collection->setPageSize($this->getPageSize());
        return $collection;
    }
}
