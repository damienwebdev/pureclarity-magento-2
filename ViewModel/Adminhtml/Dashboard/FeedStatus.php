<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Pureclarity\Core\Model\Feed\Status;

/**
 * Class Feeds
 *
 * Dashboard Feeds ViewModel for retrieving status of each type of feed
 */
class FeedStatus
{
    /** @var Status $feedStatus */
    private $feedStatus;

    /**
     * @param Status $feedStatus
     */
    public function __construct(
        Status $feedStatus
    ) {
        $this->feedStatus = $feedStatus;
    }

    /**
     * Returns whether any feeds are currently in progress
     *
     * @param integer $storeId
     *
     * @return bool
     */
    public function areFeedsInProgress(int $storeId): bool
    {
        return $this->feedStatus->areFeedsInProgress(
            ['product', 'category', 'user', 'brand', 'orders'],
            $storeId
        );
    }

    /**
     * Returns whether all feeds are currently disabled
     *
     * @param integer $storeId
     *
     * @return bool
     */
    public function areFeedsDisabled(int $storeId): bool
    {
        return $this->feedStatus->areFeedsDisabled(
            ['product', 'category', 'user', 'brand', 'orders'],
            $storeId
        );
    }

    /**
     * Returns whether the provided feed name is enabled
     *
     * @param string $feedType
     * @param integer $storeId
     *
     * @return bool
     */
    public function isFeedEnabled(string $feedType, int $storeId): bool
    {
        $status = $this->feedStatus->getFeedStatus($feedType, $storeId);
        return $status['enabled'];
    }

    /**
     * Returns the status of the product feed
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getProductFeedStatusLabel(int $storeId): string
    {
        $status = $this->feedStatus->getFeedStatus('product', $storeId);
        return $status['label'];
    }

    /**
     * Returns the status of the category feed
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getCategoryFeedStatusLabel(int $storeId): string
    {
        $status = $this->feedStatus->getFeedStatus('category', $storeId);
        return $status['label'];
    }

    /**
     * Returns the status of the user feed
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getUserFeedStatusLabel(int $storeId): string
    {
        $status = $this->feedStatus->getFeedStatus('user', $storeId);
        return $status['label'];
    }

    /**
     * Returns the status of the brand feed
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getBrandFeedStatusLabel(int $storeId): string
    {
        $status = $this->feedStatus->getFeedStatus('brand', $storeId);
        return $status['label'];
    }

    /**
     * Returns the status of the order history feed
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getOrdersFeedStatusLabel(int $storeId): string
    {
        $status = $this->feedStatus->getFeedStatus('orders', $storeId);
        return $status['label'];
    }

    /**
     * Returns the status of the product feed
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getProductFeedStatusClass(int $storeId): string
    {
        $status = $this->feedStatus->getFeedStatus('product', $storeId);
        return $status['class'];
    }

    /**
     * Returns the status of the category feed
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getCategoryFeedStatusClass(int $storeId): string
    {
        $status = $this->feedStatus->getFeedStatus('category', $storeId);
        return $status['class'];
    }

    /**
     * Returns the status of the user feed
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getUserFeedStatusClass(int $storeId): string
    {
        $status = $this->feedStatus->getFeedStatus('user', $storeId);
        return $status['class'];
    }

    /**
     * Returns the status of the brand feed
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getBrandFeedStatusClass(int $storeId): string
    {
        $status = $this->feedStatus->getFeedStatus('brand', $storeId);
        return $status['class'];
    }

    /**
     * Returns the status of the order history feed
     *
     * @param integer $storeId
     *
     * @return string
     */
    public function getOrdersFeedStatusClass(int $storeId): string
    {
        $status = $this->feedStatus->getFeedStatus('orders', $storeId);
        return $status['class'];
    }
}
