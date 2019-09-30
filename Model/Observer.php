<?php

namespace Pureclarity\Core\Model;

use Magento\Framework\Event\ObserverInterface;

class Observer implements ObserverInterface
{
    protected $service;

    public function __construct(
        \Pureclarity\Core\Helper\Service $service
    ) {
    
        $this->service = $service;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->service->setAction($observer->getFullActionName());
    }
}
