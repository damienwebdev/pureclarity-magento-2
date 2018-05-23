<?php

namespace Pureclarity\Core\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Configuration extends Template
{
    public $coreHelper;
    public $checkoutSession;
    public $customerSession;
    public $logger;
    public $cart;
    public $productCollection;
    public $request;
    public $product;
    public $category;
    public $order;
    public $orderitems = [];

    public function __construct(
        Context $context,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->coreHelper = $coreHelper;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->cart = $cart;
        $this->productCollection = $productCollection;
        $this->request = $request;
        $this->product = $registry->registry("product");
        $this->category = $registry->registry("current_category");
        parent::__construct($context, $data);
    }

    public function getConfiguration(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $formKey = $objectManager->get('Magento\Framework\Data\Form\FormKey');

        return [
            "apiUrl" => $this->getApiStartUrl(),
            "currency" => $this->getCurrencyCode(),
            "product" => $this->getProduct(),
            "state" => [
                "isActive" => $this->isActive()?true:false,
                "serversideMode" => $this->isServerSide()?true:false,
                "isLogout" => $this->isLogOut()?true:false
            ],
            "search" => [
                "isClientSearch" => $this->isClientSearch()?true:false,
                "DOMSelector" => $this->getDOMSelector(),
                "dataValue" => $this->getSearchDataValue()
            ],
            "order" => $this->getOrder(),
            "baseUrl" => $this->coreHelper->getBaseUrl(),
            "formkey" => $formKey->getFormKey(),
            "wishListUrl" =>  $this->getUrl('wishlist/index/add'),
            "compareUrl" =>  $this->getUrl('catalog/product_compare/add'),
            "showSwatches" => (int)$this->showSwatches(),
            "swatchesToShow" => $this->getNumberSwatchesPerProduct()
        ];
    }

    
    public function isActive(){
        return $this->coreHelper->isActive($this->_storeManager->getStore()->getId());
    }

    public function isServerSide(){
        return $this->coreHelper->isServerSide($this->_storeManager->getStore()->getId());
    }

    public function isLogOut(){
        return $this->request->getFullActionName() == 'customer_account_logoutSuccess';
    }

    public function getNumberSwatchesPerProduct(){
        return $this->coreHelper->getNumberSwatchesPerProduct($this->_storeManager->getStore()->getId());
    }

    public function showSwatches(){
        return $this->coreHelper->showSwatches($this->_storeManager->getStore()->getId());
    }

    public function isCheckoutSuccess(){
        if (!$this->isServerSide() && $this->request->getFullActionName() == 'checkout_onepage_success'){
            $this->initOrderData();
            return true;
        };
        return false;
    }

    public function getProduct(){
        if ($this->product != null){
            return [
                "Id" => $this->product->getId(),
                "Sku" => $this->product->getSku()
            ];
        }
        return null;
    }

    public function getApiStartUrl(){
        return $this->coreHelper->getApiStartUrl();
    }

    public function getCurrencyCode(){
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    public function getSearchDataValue(){
        if ($this->isClientSearch()){
            if ($this->isSearchPage()){
                return "navigation_search";
            }
            else if ($this->isCategoryPage() && $this->category){
                return "navigation_category:" . $this->category->getId();
            }
        }
        return "";
    }
    
    public function isClientSearch(){
        $storeId = $this->_storeManager->getStore()->getId();
        return ($this->coreHelper->isActive($storeId) &&
                !$this->coreHelper->isServerSide($storeId) &&
                ($this->isSearchPage() || $this->isCategoryPage()));
    }

    public function getDOMSelector(){
        return $this->coreHelper->getDOMSelector($this->_storeManager->getStore()->getId());
    }

    public function isSearchPage($storeId = null) {
        if ($this->coreHelper->isSearchActive($storeId) && $this->request->getFullActionName() === 'catalogsearch_result_index') {
            return true;
        }
        return false;
    }

    public function isCategoryPage($storeId = null){
        
        if ($this->coreHelper->isProdListingActive($storeId) && $this->request->getControllerName() == 'category') {
            
            if ($this->category && $this->category->getDisplayMode() !== 'PAGE') {
                return true;
            }
        }
        return false;
    }

    public function getOrder(){
        
        if (!$this->isServerSide() && $this->request->getFullActionName() == 'checkout_onepage_success'){
        
            $lastOrder = $this->checkoutSession->getLastRealOrder();
            $order = [
                "orderid" => $lastOrder['increment_id'],
                "firstname" => $lastOrder['customer_firstname'],
                "lastname" => $lastOrder['customer_lastname'],
                "postcode" => $lastOrder->getShippingAddress()['postcode'],
                "userid" => $lastOrder['customer_id'],
                "ordertotal" => $lastOrder['grand_total']
            ];

            $orderItems = [];
            $visibleItems = $lastOrder->getAllVisibleItems();
            $allItems = $lastOrder->getAllItems();
            foreach($visibleItems as $item){
                $orderItems[$item->getItemId()] = [
                    "orderid" => $lastOrder['increment_id'],
                    "refid" => $item->getItemId(),
                    "id" => $item->getProductId(), 
                    "qty" => $item->getQtyOrdered(),
                    "unitprice" => $item->getPrice(),
                    "children" => []
                ];
            }
            foreach($allItems as $item){
                if ($item->getParentItemId() && $orderItems[$item->getParentItemId()]){
                    $orderItems[$item->getParentItemId()]['children'][] = array("sku" => $item->getSku(), "qty" => $item->getQtyOrdered());
                }
            }

            return [
                "transaction" => $order,
                "items" => array_values($orderItems)
            ];
        }
        return null;
    }


}