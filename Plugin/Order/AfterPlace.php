<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Plugin\Order;

use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Pureclarity\Core\Model\Config\Source\Mode;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Serverside\Request\Admin;
use Pureclarity\Core\Model\Serverside\Request\Frontend;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\Serverside\Data\Order as OrderTrackData;

/**
 * Class AfterPlace
 *
 * Sends the order to PureClarity if in serverside mode.
 */
class AfterPlace
{
    /** @var Frontend $serversideFrontend */
    private $serversideFrontend;

    /** @var Admin $serversideAdmin */
    private $serversideAdmin;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var OrderTrackData */
    private $orderTrackData;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var State */
    private $state;
    
    /**
     * @param Frontend $frontend
     * @param Admin $admin
     * @param LoggerInterface $logger
     * @param OrderTrackData $orderTrackData
     * @param CoreConfig $coreConfig
     * @param State $state
     */
    public function __construct(
        Frontend $frontend,
        Admin $admin,
        LoggerInterface $logger,
        OrderTrackData $orderTrackData,
        CoreConfig $coreConfig,
        State $state
    ) {
        $this->serversideFrontend = $frontend;
        $this->serversideAdmin    = $admin;
        $this->logger             = $logger;
        $this->orderTrackData     = $orderTrackData;
        $this->coreConfig         = $coreConfig;
        $this->state              = $state;
    }

    /**
     * @param OrderManagementInterface $orderManagementInterface
     * @param Order $order
     * @return Order
     */
    public function afterPlace($orderManagementInterface, $order)
    {
        if ($this->coreConfig->isActive($order->getStoreId())
            && ($this->coreConfig->getMode($order->getStoreId()) === Mode::MODE_SERVERSIDE
                || $this->getArea() === 'adminhtml')
        ) {
            $orderData = $this->orderTrackData->buildOrderTrack($order);
            if ($this->getArea() === 'adminhtml') {
                $this->serversideAdmin->setStoreId($order->getStoreId());
                $this->serversideAdmin->execute([
                    'moto_order' => $orderData
                ]);
            } else {
                $this->serversideFrontend->setStoreId($order->getStoreId());
                $this->serversideFrontend->execute([
                    'order' => $orderData
                ]);
            }
        }

        return $order;
    }

    /**
     * @return string
     */
    public function getArea()
    {
        $area = '';
        try {
            $area = $this->state->getAreaCode();
        } catch (LocalizedException $e) {
            $this->logger->debug('PURECLARITY ERROR in afterPlace plugin: ' . $e->getMessage());
        }

        return $area;
    }
}
