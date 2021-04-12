<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed;

use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed\State\Request;
use Pureclarity\Core\Model\Feed\State\Progress;
use Pureclarity\Core\Model\Feed\State\Error;

/**
 * Class Requester
 *
 * Controls the requesting of feeds
 */
class Requester
{
    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Request */
    private $request;

    /** @var Progress */
    private $progress;

    /** @var Error */
    private $error;

    /**
     * @param Request $request
     * @param Progress $progress
     * @param Error $error
     */
    public function __construct(
        CoreConfig $coreConfig,
        Request $request,
        Progress $progress,
        Error $error
    ) {
        $this->coreConfig = $coreConfig;
        $this->request    = $request;
        $this->progress   = $progress;
        $this->error      = $error;
    }

    /**
     * Runs all feed types for the given store
     * @param int $storeId
     */
    public function requestFeeds(int $storeId, array $feeds, $force = false): void
    {
        if ($force || $this->coreConfig->isActive($storeId)) {
            $this->request->requestFeeds($storeId, $feeds);

            foreach (Runner::VALID_FEED_TYPES as $feed) {
                $this->error->saveFeedError($storeId, $feed, '');
                $this->progress->updateProgress($storeId, $feed, '');
            }
        }
    }
}
