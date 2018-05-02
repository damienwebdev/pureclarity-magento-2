<?php

namespace Pureclarity\Core\Model;

use Magento\Framework\Event\ObserverInterface;

class PostDispatchObserver implements ObserverInterface
{
    protected $logger;
    protected $service;
    protected $request;
    protected $coreHelper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Request\Http $request,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Pureclarity\Core\Helper\Service $service)
    {
        $this->logger = $logger;
        $this->request = $request;
        $this->coreHelper = $coreHelper;
        $this->service = $service;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //$this->service->dispatch();
    }

    private function isSearchPage(){
        return $this->request->getFullActionName() == 'catalogsearch_result_index';
    }

    private function isCategoryPage(){
        return $this->request->getControllerName() == 'category';
    }
}
