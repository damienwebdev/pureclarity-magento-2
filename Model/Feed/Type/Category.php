<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type;

use PureClarity\Api\Feed\Feed;
use PureClarity\Api\Feed\Type\CategoryFactory;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;
use Pureclarity\Core\Api\CategoryFeedDataManagementInterface;
use Pureclarity\Core\Api\CategoryFeedRowDataManagementInterface;

/**
 * Class Category
 *
 * Handles running of category feed
 */
class Category implements FeedManagementInterface
{
    /** @var CategoryFactory */
    private $categoryFeedFactory;

    /** @var CategoryFeedDataManagementInterface */
    private $feedDataHandler;

    /** @var CategoryFeedRowDataManagementInterface */
    private $rowDataHandler;

    /**
     * @param CategoryFactory $categoryFeedFactory
     * @param CategoryFeedDataManagementInterface $feedDataHandler
     * @param CategoryFeedRowDataManagementInterface $rowDataHandler
     */
    public function __construct(
        CategoryFactory $categoryFeedFactory,
        CategoryFeedDataManagementInterface $feedDataHandler,
        CategoryFeedRowDataManagementInterface $rowDataHandler
    ) {
        $this->categoryFeedFactory = $categoryFeedFactory;
        $this->feedDataHandler     = $feedDataHandler;
        $this->rowDataHandler      = $rowDataHandler;
    }

    /**
     * Returns whether this feed is enabled
     *
     * @param int $storeId
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isEnabled(int $storeId): bool
    {
        return true;
    }

    /**
     * Gets the feed builder class from the PureClarity API
     *
     * @param string $accessKey
     * @param string $secretKey
     * @param string $region
     * @return Feed
     */
    public function getFeedBuilder(string $accessKey, string $secretKey, string $region): Feed
    {
        return $this->categoryFeedFactory->create([
            'accessKey' => $accessKey,
            'secretKey' => $secretKey,
            'region' => $region
        ]);
    }

    /**
     * Gets the category feed data handler
     *
     * @return CategoryFeedDataManagementInterface
     */
    public function getFeedDataHandler(): FeedDataManagementInterface
    {
        return $this->feedDataHandler;
    }

    /**
     * Gets the category feed row data handler
     *
     * @return CategoryFeedRowDataManagementInterface
     */
    public function getRowDataHandler(): FeedRowDataManagementInterface
    {
        return $this->rowDataHandler;
    }

    /**
     * Returns whether this feed requires emulation
     * @return bool
     */
    public function requiresEmulation(): bool
    {
        return false;
    }
}
