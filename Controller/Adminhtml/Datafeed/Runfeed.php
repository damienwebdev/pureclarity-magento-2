<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Controller\Adminhtml\Datafeed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\CronFactory;

/**
 * Class Runfeed
 *
 * controller for pureclarity/dashboard/runfeed POST request
 */
class Runfeed extends Action
{
    /** @var CronFactory  */
    private $coreCronFactory;

    /** @var StoreManagerInterface  */
    private $storeManager;

    /**
     * @param Context $context
     * @param CronFactory $coreCronFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        CronFactory $coreCronFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->coreCronFactory = $coreCronFactory;
        $this->storeManager    = $storeManager;
        parent::__construct(
            $context
        );
    }
    
    public function execute()
    {
        try {
            $storeId =  (int)$this->getRequest()->getParam('storeid');

            if ($storeId === 0) {
                $store = $this->storeManager->getDefaultStoreView();
                if ($store) {
                    $storeId = $store->getId();
                }
            }

            $model = $this->coreCronFactory->create();
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
            $model->scheduleSelectedFeeds($storeId, $feeds);
        } catch (\Exception $e) {
            $this->getResponse()
                ->clearHeaders()
            ->setStatusCode(Http::STATUS_CODE_500)
                ->setHeader('Content-type', 'text/html')
                ->setBody($e->getMessage());
        }
    }
}
