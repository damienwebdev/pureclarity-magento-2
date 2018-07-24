<?php

namespace Pureclarity\Core\Model;

use Magento\Framework\Event\ObserverInterface;

class Observer implements ObserverInterface
{
    protected $logger;
    protected $service;
    protected $coreHelper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Pureclarity\Core\Helper\Service $service
    ) {
    
        $this->logger = $logger;
        $this->service = $service;
        $this->coreHelper = $coreHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->coreHelper->isSearchActive()) {
            $observer->getLayout()->getUpdate()->addHandle('pureclarity_autocomplete_handle');
        }

        $this->service->setAction($observer->getFullActionName());
    }
}
