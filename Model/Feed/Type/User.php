<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type;

use PureClarity\Api\Feed\Feed;
use PureClarity\Api\Feed\Type\UserFactory;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;
use Pureclarity\Core\Api\UserFeedDataManagementInterface;
use Pureclarity\Core\Api\UserFeedRowDataManagementInterface;

/**
 * Class User
 *
 * Handles running of user feed
 */
class User implements FeedManagementInterface
{
    /** @var UserFactory */
    private $userFeedFactory;

    /** @var UserFeedDataManagementInterface */
    private $feedDataHandler;

    /** @var UserFeedRowDataManagementInterface */
    private $rowDataHandler;

    /**
     * @param UserFactory $userFeedFactory
     * @param UserFeedDataManagementInterface $feedDataHandler
     * @param UserFeedRowDataManagementInterface $rowDataHandler
     */
    public function __construct(
        UserFactory $userFeedFactory,
        UserFeedDataManagementInterface $feedDataHandler,
        UserFeedRowDataManagementInterface $rowDataHandler
    ) {
        $this->userFeedFactory = $userFeedFactory;
        $this->feedDataHandler = $feedDataHandler;
        $this->rowDataHandler  = $rowDataHandler;
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
        return $this->userFeedFactory->create([
            'accessKey' => $accessKey,
            'secretKey' => $secretKey,
            'region' => $region
        ]);
    }

    /**
     * Gets the user feed data handler
     *
     * @return UserFeedDataManagementInterface
     */
    public function getFeedDataHandler(): FeedDataManagementInterface
    {
        return $this->feedDataHandler;
    }

    /**
     * Gets the user feed row data handler
     *
     * @return UserFeedRowDataManagementInterface
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
