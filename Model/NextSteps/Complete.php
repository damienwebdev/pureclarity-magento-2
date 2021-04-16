<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\NextSteps;

use Psr\Log\LoggerInterface;
use PureClarity\Api\NextSteps\CompleteFactory;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class Complete
 *
 * NextSteps Complete API caller model
 */
class Complete
{
    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CompleteFactory $completeFactory */
    private $completeFactory;

    /**
     * @param CoreConfig $coreConfig
     * @param LoggerInterface $logger
     * @param CompleteFactory $completeFactory
     */
    public function __construct(
        CoreConfig $coreConfig,
        LoggerInterface $logger,
        CompleteFactory $completeFactory
    ) {
        $this->coreConfig      = $coreConfig;
        $this->logger          = $logger;
        $this->completeFactory = $completeFactory;
    }

    /**
     * Sends an API call to PureClarity to mark a next step as complete.
     *
     * @param integer $storeId
     * @param string $nextStepId
     */
    public function markNextStepComplete($storeId, $nextStepId)
    {
        $complete = $this->completeFactory->create([
            'accessKey' => $this->coreConfig->getAccessKey($storeId),
            'nextStepId' => $nextStepId,
            'region' => $this->coreConfig->getRegion($storeId)
        ]);

        try {
            $complete->request();
        } catch (\Exception $e) {
            $this->logger->error('PureClarity Next Step complete call Error: ' . $e->getMessage());
        }
    }
}
