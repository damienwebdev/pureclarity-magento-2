<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Pureclarity\Core\Model\FeedStatus as FeedStatusModel;

/**
 * Class Feeds
 *
 * Dashboard Feeds ViewModel for retrieving status of each type of feed
 */
class FeedStatus implements ArgumentInterface
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
        return $this->feedStatus->getAreFeedsInProgress(['product', 'category', 'user', 'brand', 'order_history']);
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
     * @return mixed[]
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
     * @return mixed[]
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
     * @return mixed[]
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
     * @return mixed[]
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
     * @return mixed[]
     */
    public function getOrdersFeedStatusLabel($storeId)
    {
        $status = $this->feedStatus->getFeedStatus('orders', $storeId);
        return $status['label'];
    }
}
