<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Sales\Model\Order;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Helper\Service;

/**
 * Class MotoOrder
 *
 * Tracking for MOTO orders
 */
class MotoOrder implements ObserverInterface
{
    /** @var Data */
    private $coreHelper;

    /** @var Order */
    private $salesOrder;

    /** @var Service */
    private $service;

    /**
     * @param Data $coreHelper
     * @param Order $salesOrder
     * @param Service $service
     */
    public function __construct(
        Data $coreHelper,
        Order $salesOrder,
        Service $service
    ) {
        $this->coreHelper = $coreHelper;
        $this->salesOrder = $salesOrder;
        $this->service    = $service;
    }

    /**
     * Installs attributes required for PureClarity.
     *
     * @param EventObserver $observer Magento event observer
     *
     * @return void
     */
    public function execute(EventObserver $observer)
    {

        $observerOrder = $observer->getEvent()->getOrder();

        if (!$this->coreHelper->isActive($observerOrder->getStoreId())) {
            return;
        }

        $motoOrder = $this->salesOrder->loadByIncrementIdAndStoreId(
            $observerOrder->getIncrementId(),
            $observerOrder->getStoreId()
        );

        $this->service->addTrackingEvent('moto_order_track', $this->coreHelper->getOrderForTracking($motoOrder));
        $this->service->dispatch(true);
    }
}
