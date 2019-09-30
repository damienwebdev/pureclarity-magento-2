<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Helper\Service\CustomerDetails;

/**
 * Class Configuration
 *
 * builds config array for PureClarity API Javascript
 */
class Configuration extends Template
{
    /** @var Product */
    private $product;

    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var Session $checkoutSession */
    private $checkoutSession;

    /** @var Http $request */
    private $request;

    /** @var Registry $registry */
    private $registry;

    /** @var ProductMetadataInterface $productMetadata */
    private $productMetadata;
    
    /** @var CustomerDetails $customerDetails */
    private $customerDetails;

    /**
     * @param Context $context
     * @param Data $coreHelper
     * @param Session $checkoutSession
     * @param Http $request
     * @param Registry $registry
     * @param ProductMetadataInterface $productMetadata
     * @param CustomerDetails $customerDetails
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $coreHelper,
        Session $checkoutSession,
        Http $request,
        Registry $registry,
        ProductMetadataInterface $productMetadata,
        CustomerDetails $customerDetails,
        array $data = []
    ) {
        $this->coreHelper      = $coreHelper;
        $this->checkoutSession = $checkoutSession;
        $this->request         = $request;
        $this->productMetadata = $productMetadata;
        $this->customerDetails = $customerDetails;
        parent::__construct($context, $data);
    }

    public function getConfiguration()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $formKey = $objectManager->get('Magento\Framework\Data\Form\FormKey');
        $customerDetails = $this->customerDetails->getCustomerDetails();

        return [
            "apiUrl" => $this->getApiStartUrl(),
            "currency" => $this->getCurrencyCode(),
            "product" => $this->getProduct(),
            "state" => [
                "isActive" => $this->isActive()?true:false,
                "serversideMode" => false,
                "isLogout" => $this->isLogOut()?true:false
            ],
            "customerDetails" => $customerDetails,
            "order" => $this->getOrder(),
            "baseUrl" => $this->coreHelper->getBaseUrl(),
            "formkey" => $formKey->getFormKey(),
            "wishListUrl" =>  $this->getUrl('wishlist/index/add'),
            "compareUrl" =>  $this->getUrl('catalog/product_compare/add'),
            "showSwatches" => (int)$this->showSwatches(),
            "swatchesToShow" => $this->getNumberSwatchesPerProduct(),
            "swatchRenderer" => $this->getSwatchRendererPath(),
        ];
    }
    
    public function isActive()
    {
        return $this->coreHelper->isActive($this->_storeManager->getStore()->getId());
    }

    public function isLogOut()
    {
        return $this->request->getFullActionName() == 'customer_account_logoutSuccess';
    }

    public function getNumberSwatchesPerProduct()
    {
        return $this->coreHelper->getNumberSwatchesPerProduct($this->_storeManager->getStore()->getId());
    }

    public function showSwatches()
    {
        return $this->coreHelper->showSwatches($this->_storeManager->getStore()->getId());
    }

    public function getProduct()
    {
        $this->product = $this->registry->registry("product");
        if ($this->product != null) {
            return [
                "Id" => $this->product->getId(),
                "Sku" => $this->product->getSku()
            ];
        }
        return null;
    }

    public function getApiStartUrl()
    {
        return $this->coreHelper->getApiStartUrl();
    }

    public function getCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    public function getOrder()
    {
        
        if ($this->request->getFullActionName() == 'checkout_onepage_success') {
            $lastOrder = $this->checkoutSession->getLastRealOrder();
            $order = [
                "orderid" => $lastOrder['increment_id'],
                "firstname" => $lastOrder['customer_firstname'],
                "lastname" => $lastOrder['customer_lastname'],
                "postcode" => $lastOrder->getShippingAddress()['postcode'],
                "userid" => $lastOrder['customer_id'],
                "groupid" => $lastOrder['customer_group_id'],
                "ordertotal" => $lastOrder['grand_total']
            ];

            $orderItems = [];
            $visibleItems = $lastOrder->getAllVisibleItems();
            $allItems = $lastOrder->getAllItems();
            foreach ($visibleItems as $item) {
                $orderItems[$item->getItemId()] = [
                    "orderid" => $lastOrder['increment_id'],
                    "refid" => $item->getItemId(),
                    "id" => $item->getProductId(),
                    "qty" => $item->getQtyOrdered(),
                    "unitprice" => $item->getPrice(),
                    "children" => []
                ];
            }
            foreach ($allItems as $item) {
                if ($item->getParentItemId() && $orderItems[$item->getParentItemId()]) {
                    $orderItems[$item->getParentItemId()]['children'][] = [
                        "sku" => $item->getSku(),
                        "qty" => $item->getQtyOrdered()
                    ];
                }
            }
            
            $order['items'] = array_values($orderItems);
            
            return $order;
        }
        return null;
    }

    private function getSwatchRendererPath()
    {
        if (version_compare($this->productMetadata->getVersion(), '2.1.0', '<')) {
            return 'Magento_Swatches/js/SwatchRenderer';
        } else {
            return 'Magento_Swatches/js/swatch-renderer';
        }
    }
}
