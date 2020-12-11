<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Psr\Log\LoggerInterface;
use Pureclarity\Core\Helper\Serializer;
use PureClarity\Api\Info\DashboardFactory;

/**
 * Class Dashboard
 *
 * Dashboard info API caller model
 */
class Dashboard
{
    /** @var mixed[] $dashBoardInfo */
    private $dashboardInfo = [];

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var DashboardFactory $dashboardFactory */
    private $dashboardFactory;

    /**
     * @param CoreConfig $coreConfig
     * @param Serializer $serializer
     * @param LoggerInterface $logger
     * @param DashboardFactory $dashboardFactory
     */
    public function __construct(
        CoreConfig $coreConfig,
        Serializer $serializer,
        LoggerInterface $logger,
        DashboardFactory $dashboardFactory
    ) {
        $this->coreConfig       = $coreConfig;
        $this->serializer       = $serializer;
        $this->logger           = $logger;
        $this->dashboardFactory = $dashboardFactory;
    }

    /**
     * Gets the Next Steps for this application from PureClarity
     * @param int $storeId
     * @return array|mixed
     */
    public function getNextSteps($storeId)
    {
        $dashboard = $this->getDashboardInfo($storeId);
        return isset($dashboard['NextSteps']) ? $dashboard['NextSteps'] : [];
    }

    /**
     * Gets the Stats for this application from PureClarity
     * @param int $storeId
     * @return array|mixed
     */
    public function getStats($storeId)
    {
        $dashboard = $this->getDashboardInfo($storeId);
        return isset($dashboard['Stats']) ? $dashboard['Stats'] : [];
    }

    /**
     * Gets the Account Status info for this application from PureClarity
     * @param int $storeId
     * @return array|mixed
     */
    public function getAccountStatus($storeId)
    {
        $dashboard = $this->getDashboardInfo($storeId);
        return isset($dashboard['Account']) ? $dashboard['Account'] : [];
    }

    /**
     * @return mixed[]
     */
    public function getDashboardInfo($storeId)
    {
        if (!isset($this->dashboardInfo[$storeId])) {
            $dash = $this->dashboardFactory->create([
                'accessKey' => $this->coreConfig->getAccessKey($storeId),
                'secretKey' => $this->coreConfig->getSecretKey($storeId),
                'region' => $this->coreConfig->getRegion($storeId)
            ]);

            $this->dashboardInfo[$storeId] = [];

            try {
                $response = $dash->request();
                $this->dashboardInfo[$storeId] = $this->serializer->unserialize($response['body']);
            } catch (\Exception $e) {
                $this->logger->error('PureClarity Dashboard Error: ' . $e->getMessage());
            }
        }

        return $this->dashboardInfo[$storeId];
    }
}
