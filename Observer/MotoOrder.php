<?php 
namespace Pureclarity\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

class MotoOrder implements ObserverInterface {

    protected $logger;
    protected $coreHelper;
    protected $service;
    protected $salesOrder;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Magento\Sales\Model\Order $salesOrder,
        \Pureclarity\Core\Helper\Service $service
    ) {
        $this->logger = $logger;
        $this->coreHelper = $coreHelper;
        $this->service = $service;
        $this->salesOrder = $salesOrder;
    }

    public function execute(EventObserver $observer){

        $observerOrder = $observer->getEvent()->getOrder();

        if(!$this->coreHelper->isActive($observerOrder->getStoreId()))
            return;

        $motoOrder = $this->salesOrder->loadByIncrementIdAndStoreId($observerOrder->getIncrementId(), $observerOrder->getStoreId());        

        $this->service->addTrackingEvent('moto_order_track', $this->coreHelper->getOrderForTracking($motoOrder));
        $this->service->dispatch(true);

    }

}