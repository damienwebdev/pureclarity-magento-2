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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Helper\Service\CustomerDetails;
use Pureclarity\Core\Helper\Service\Url;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class Configuration
 *
 * builds config array for PureClarity API Javascript
 */
class Configuration extends Template
{
    /** @var integer $storeId */
    private $storeId;

    /** @var Product $product */
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

    /** @var Url $serviceUrl */
    private $serviceUrl;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /**
     * @param Context $context
     * @param Data $coreHelper
     * @param Session $checkoutSession
     * @param Http $request
     * @param Registry $registry
     * @param ProductMetadataInterface $productMetadata
     * @param CustomerDetails $customerDetails
     * @param Url $serviceUrl
     * @param CoreConfig $coreConfig
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
        Url $serviceUrl,
        CoreConfig $coreConfig,
        array $data = []
    ) {
        $this->coreHelper      = $coreHelper;
        $this->checkoutSession = $checkoutSession;
        $this->request         = $request;
        $this->registry        = $registry;
        $this->productMetadata = $productMetadata;
        $this->customerDetails = $customerDetails;
        $this->serviceUrl      = $serviceUrl;
        $this->coreConfig      = $coreConfig;
        parent::__construct($context, $data);
    }

    public function getConfiguration()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $formKey = $objectManager->get('Magento\Framework\Data\Form\FormKey');
        $customerDetails = $this->customerDetails->getCustomerDetails();

        return [
            "apiUrl" => $this->serviceUrl->getClientScriptUrl($this->coreConfig->getAccessKey($this->getStoreId())),
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
        return $this->coreConfig->isActive($this->getStoreId());
    }

    /**
     * Gets the current store ID
     *
     * @return int
     */
    public function getStoreId()
    {
        if ($this->storeId === null) {
            try {
                $this->storeId = $this->_storeManager->getStore()->getId();
            } catch (NoSuchEntityException $e) {
                $this->storeId = 0;
            }
        }
        return $this->storeId;
    }

    public function isLogOut()
    {
        return $this->request->getFullActionName() == 'customer_account_logoutSuccess';
    }

    public function getNumberSwatchesPerProduct()
    {
        return $this->coreConfig->getNumberSwatchesPerProduct($this->getStoreId());
    }

    public function showSwatches()
    {
        return $this->coreConfig->showSwatches($this->getStoreId());
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
                "ordertotal" => $lastOrder['grand_total'],
                'email' => $lastOrder['customer_email']
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
