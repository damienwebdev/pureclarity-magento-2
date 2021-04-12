<?php
declare(strict_types=1);

/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Controller\Adminhtml\Datafeed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\Feed\Requester;

/**
 * Class Runfeed
 *
 * controller for pureclarity/dashboard/runfeed POST request
 */
class Runfeed extends Action
{
    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var Requester $feedRequest */
    private $feedRequest;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Requester $feedRequest
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Requester $feedRequest
    ) {
        $this->storeManager = $storeManager;
        $this->feedRequest  = $feedRequest;

        parent::__construct(
            $context
        );
    }

    /**
     * Requests that the selected feeds are run
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        try {
            $storeId =  (int)$this->getRequest()->getParam('storeid');

            if ($storeId === 0) {
                $store = $this->storeManager->getDefaultStoreView();
                if ($store) {
                    $storeId = (int)$store->getId();
                }
            }

            $feeds = [];
            if ($this->getRequest()->getParam('product') == 'true') {
                $feeds[] = 'product';
            }
            if ($this->getRequest()->getParam('category') == 'true') {
                $feeds[] = 'category';
            }
            if ($this->getRequest()->getParam('brand') == 'true') {
                $feeds[] = 'brand';
            }
            if ($this->getRequest()->getParam('user') == 'true') {
                $feeds[] = 'user';
            }
            if ($this->getRequest()->getParam('orders') == 'true') {
                $feeds[] = 'orders';
            }

            $this->feedRequest->requestFeeds($storeId, $feeds);
        } catch (\Exception $e) {
            $this->getResponse()
                ->clearHeaders()
            ->setStatusCode(Http::STATUS_CODE_500)
                ->setHeader('Content-type', 'text/html')
                ->setBody($e->getMessage());
        }
    }
}
