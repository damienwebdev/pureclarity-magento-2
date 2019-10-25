<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Pureclarity\Core\Model\FeedStatus as FeedStatusModel;

/**
 * Class Feeds
 *
 * Dashboard Feeds ViewModel for retrieving status of each type of feed
 */
class FeedStatus
{
    /** @var FeedStatusModel $feedStatus */
    private $feedStatus;

    /**
     * @param FeedStatusModel $feedStatus
     */
    public function __construct(
        FeedStatusModel $feedStatus
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
    public function getAreFeedsInProgress($storeId)
    {
        return $this->feedStatus->getAreFeedsInProgress(
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
    public function getAreFeedsDisabled($storeId)
    {
        return $this->feedStatus->getAreFeedsDisabled(
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
    public function isFeedEnabled($feedType, $storeId)
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
    public function getProductFeedStatusLabel($storeId)
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
    public function getCategoryFeedStatusLabel($storeId)
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
    public function getUserFeedStatusLabel($storeId)
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
    public function getBrandFeedStatusLabel($storeId)
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
    public function getOrdersFeedStatusLabel($storeId)
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
    public function getProductFeedStatusClass($storeId)
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
    public function getCategoryFeedStatusClass($storeId)
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
    public function getUserFeedStatusClass($storeId)
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
    public function getBrandFeedStatusClass($storeId)
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
    public function getOrdersFeedStatusClass($storeId)
    {
        $status = $this->feedStatus->getFeedStatus('orders', $storeId);
        return $status['class'];
    }
}
