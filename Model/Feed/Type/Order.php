<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type;

use Pureclarity\Core\Api\FeedDataManagementInterface;
use PureClarity\Api\Feed\Type\OrderFactory;
use Pureclarity\Core\Api\OrderFeedDataManagementInterface;
use Pureclarity\Core\Api\OrderFeedRowDataManagementInterface;
use PureClarity\Api\Feed\Feed;
use Pureclarity\Core\Api\FeedManagementInterface;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;

/**
 * Class Order
 *
 * Handles running of order feed
 */
class Order implements FeedManagementInterface
{
    /** @var OrderFactory */
    private $orderFeedFactory;

    /** @var OrderFeedDataManagementInterface */
    private $feedDataHandler;

    /** @var OrderFeedRowDataManagementInterface */
    private $rowDataHandler;

    /**
     * @param OrderFactory $orderFeedFactory
     * @param OrderFeedDataManagementInterface $feedDataHandler
     * @param OrderFeedRowDataManagementInterface $rowDataHandler
     */
    public function __construct(
        OrderFactory $orderFeedFactory,
        OrderFeedDataManagementInterface $feedDataHandler,
        OrderFeedRowDataManagementInterface $rowDataHandler
    ) {
        $this->orderFeedFactory = $orderFeedFactory;
        $this->feedDataHandler = $feedDataHandler;
        $this->rowDataHandler  = $rowDataHandler;
    }

    /**
     * Returns whether this feed is enabled
     *
     * @param int $storeId
     * @return bool
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
        return $this->orderFeedFactory->create([
            'accessKey' => $accessKey,
            'secretKey' => $secretKey,
            'region' => $region
        ]);
    }

    /**
     * Gets the order feed data handler
     *
     * @return OrderFeedDataManagementInterface
     */
    public function getFeedDataHandler(): FeedDataManagementInterface
    {
        return $this->feedDataHandler;
    }

    /**
     * Gets the order feed row data handler
     *
     * @return OrderFeedRowDataManagementInterface
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
