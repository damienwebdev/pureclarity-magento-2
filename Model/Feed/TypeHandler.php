<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed;

use Pureclarity\Core\Model\Feed\Type\CategoryFactory;
use Pureclarity\Core\Model\Feed\Type\BrandFactory;
use Pureclarity\Core\Model\Feed\Type\UserFactory;
use Pureclarity\Core\Model\Feed\Type\OrderFactory;
use Pureclarity\Core\Api\FeedManagementInterface;
use PureClarity\Api\Feed\Feed;
use InvalidArgumentException;

/**
 * Class TypeHandler
 *
 * Loads classes responsible for handling each feed type.
 */
class TypeHandler
{
    /** @var CategoryFactory */
    private $categoryFeed;

    /** @var BrandFactory */
    private $brandFeed;

    /** @var UserFactory */
    private $userFeed;

    /** @var OrderFactory */
    private $orderFeed;

    /**
     * @param CategoryFactory $categoryFeed
     * @param BrandFactory $brandFeed
     * @param UserFactory $userFeed
     * @param OrderFactory $userFeed
     */
    public function __construct(
        CategoryFactory $categoryFeed,
        BrandFactory $brandFeed,
        UserFactory $userFeed,
        OrderFactory $orderFeed
    ) {
        $this->categoryFeed = $categoryFeed;
        $this->brandFeed    = $brandFeed;
        $this->userFeed     = $userFeed;
        $this->orderFeed    = $orderFeed;
    }

    /**
     * Gets the feed handler class for the given feed type
     * @param string $type
     * @return FeedManagementInterface
     * @throws InvalidArgumentException
     */
    public function getFeedHandler(string $type): FeedManagementInterface
    {
        switch ($type) {
            case Feed::FEED_TYPE_CATEGORY:
                $feedHandler = $this->categoryFeed->create();
                break;
            case Feed::FEED_TYPE_BRAND:
                $feedHandler = $this->brandFeed->create();
                break;
            case Feed::FEED_TYPE_USER:
                $feedHandler = $this->userFeed->create();
                break;
            case Feed::FEED_TYPE_ORDER:
                $feedHandler = $this->orderFeed->create();
                break;
            default:
                throw new InvalidArgumentException('PureClarity feed type not recognised: ' . $type);
        }

        return $feedHandler;
    }
}
