<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Pureclarity\Core\Model\FeedStatus;

/**
 * Class Feeds
 *
 * Dashboard Feeds ViewModel for retrieving status of each type of feed
 */
class Feeds implements ArgumentInterface
{
    /** @var FeedStatus $feedStatus */
    private $feedStatus;

    /**
     * @param FeedStatus $feedStatus
     */
    public function __construct(
        FeedStatus $feedStatus
    ) {
        $this->feedStatus = $feedStatus;
    }

    /**
     * Returns the status of the product feed
     *
     * @return string
     */
    public function getProductFeedStatus()
    {
        return $this->feedStatus->getFeedStatus('product');
    }

    /**
     * Returns the status of the category feed
     *
     * @return string
     */
    public function getCategoryFeedStatus()
    {
        return $this->feedStatus->getFeedStatus('category');
    }

    /**
     * Returns the status of the user feed
     *
     * @return string
     */
    public function getUserFeedStatus()
    {
        return $this->feedStatus->getFeedStatus('user');
    }

    /**
     * Returns the status of the brand feed
     *
     * @return string
     */
    public function getBrandFeedStatus()
    {
        return $this->feedStatus->getFeedStatus('brand');
    }

    /**
     * Returns the status of the order history feed
     *
     * @return string
     */
    public function getOrderHistoryFeedStatus()
    {
        return $this->feedStatus->getFeedStatus('order_history');
    }
}
