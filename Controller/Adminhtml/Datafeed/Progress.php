<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Controller\Adminhtml\Datafeed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\FeedStatus;

class Progress extends Action
{
    /** @var JsonFactory $resultJsonFactory */
    private $resultJsonFactory;

    /** @var FeedStatus $feedStatus */
    private $feedStatus;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FeedStatus $feedStatus,
        StoreManagerInterface $storeManager
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->feedStatus        = $feedStatus;
        $this->storeManager      = $storeManager;

        parent::__construct(
            $context
        );
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $storeId =  (int)$this->getRequest()->getParam('storeid');

        if (empty($storeId)) {
            $store = $this->storeManager->getDefaultStoreView();
            if ($store) {
                $storeId = $store->getId();
            }
        }

        $status = [
            'product' => $this->feedStatus->getFeedStatus('product', $storeId),
            'category' => $this->feedStatus->getFeedStatus('category', $storeId),
            'user' => $this->feedStatus->getFeedStatus('user', $storeId),
            'brand' => $this->feedStatus->getFeedStatus('brand', $storeId),
            'orders' => $this->feedStatus->getFeedStatus('orders', $storeId)
        ];

        return $this->resultJsonFactory->create()->setData($status);
    }
}
