<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class Index
 *
 * controller for pureclarity/dashboard/index page
 */
class Index extends Action
{
    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory;

    /** @var RequestInterface $request */
    protected $request;

    /** @var StoreManagerInterface $storeManager */
    protected $storeManager;

    /** @var CoreConfig */
    private $coreConfig;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        CoreConfig $coreConfig
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->request           = $request;
        $this->storeManager      = $storeManager;
        $this->coreConfig        = $coreConfig;
    }

    /**
     * Load the page defined in view/adminhtml/layout/pureclarity_dashboard_index.xml
     *
     * @return Page
     */
    public function execute()
    {
        // if multistore, need to pre-select a store
        if ($this->storeManager->hasSingleStore() === false) {
            $this->request->setParams(['store' => $this->preSelectStore()]);
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_Backend::content');
        return $resultPage;
    }

    /**
     * Pre-selects the store if none is chosen already
     *
     * Note: this is done here, so that the dropdown at the top of the page shows the correct value
     * @return int
     */
    public function preSelectStore()
    {
        // if there is one specified in the request, prioritise that.
        if ((int)$this->request->getParam('store')) {
            return (int)$this->request->getParam('store');
        }

        // Check all stores for a configured store, and return the first
        foreach ($this->storeManager->getStores() as $storeView) {
            $accessKey = $this->coreConfig->getAccessKey($storeView->getId());
            $secretKey = $this->coreConfig->getSecretKey($storeView->getId());
            if ($accessKey && $secretKey) {
                return $storeView->getId();
            }
        }

        // No specified store & no configured store, so use the default store view.
        $store = $this->storeManager->getDefaultStoreView();

        if ($store) {
            return (int)$store->getId();
        }

        // If for some odd reason, we don't have any of these, return 0.
        return 0;
    }
}
