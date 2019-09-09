<?php
namespace Pureclarity\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

class MotoOrder implements ObserverInterface
{

    /**
     * Logger interface
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * PureClarity helper
     *
     * @var \Pureclarity\Core\Helper\Data
     */
    private $coreHelper;

    /**
     * Sales order
     *
     * @var \Magento\Sales\Model\Order
     */
    private $salesOrder;

    /**
     * PureClarity service class
     *
     * @var \Pureclarity\Core\Helper\Service
     */
    private $service;

    /**
     * Constructor to inject dependencies into class.
     *
     * @param \Psr\Log\LoggerInterface         $logger     For logging
     * @param \Pureclarity\Core\Helper\Data    $coreHelper PureClarity helper
     * @param \Magento\Sales\Model\Order       $salesOrder Sales order
     * @param \Pureclarity\Core\Helper\Service $service    PureClarity service class
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Magento\Sales\Model\Order $salesOrder,
        \Pureclarity\Core\Helper\Service $service
    ) {
        $this->logger = $logger;
        $this->coreHelper = $coreHelper;
        $this->salesOrder = $salesOrder;
        $this->service = $service;
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
