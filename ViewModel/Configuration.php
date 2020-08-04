<?php

/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Helper\Serializer;
use Pureclarity\Core\Helper\Service\Url;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Framework\Locale\Format;
use Magento\Framework\UrlInterface;

/**
 * Class Configuration
 *
 * builds config array for PureClarity API Javascript
 */
class Configuration
{
    /** @var integer */
    private $storeId;

    /** @var Http */
    private $request;

    /** @var Registry */
    private $registry;

    /** @var Url */
    private $serviceUrl;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var Serializer */
    private $serializer;

    /** @var Format */
    private $format;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Http $request
     * @param Registry $registry
     * @param Url $serviceUrl
     * @param CoreConfig $coreConfig
     * @param Serializer $serializer
     * @param Format $format
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Http $request,
        Registry $registry,
        Url $serviceUrl,
        CoreConfig $coreConfig,
        Serializer $serializer,
        Format $format,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->request         = $request;
        $this->registry        = $registry;
        $this->serviceUrl      = $serviceUrl;
        $this->coreConfig      = $coreConfig;
        $this->serializer      = $serializer;
        $this->format          = $format;
        $this->urlBuilder      = $urlBuilder;
        $this->storeManager    = $storeManager;
        $this->logger          = $logger;
    }

    /**
     * Converts the configuration to a json encoded string
     *
     * @return string
     */
    public function getConfigurationJson()
    {
        return $this->serializer->serialize($this->getConfiguration());
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return [
            'apiUrl' => $this->getApiUrl(),
            'currency' => $this->getCurrencyCode(),
            'product' => $this->getProduct(),
            'state' => [
                'isActive' => $this->isActive(),
                'mode' => $this->getMode(),
                'isLogout' => $this->isLogOut()
            ],
            'page' => $this->getPageContext(),
            'baseUrl' => $this->getBaseUrl(),
            'wishListUrl' => $this->getWishlistUrl(),
            'compareUrl' => $this->getCompareUrl(),
            'showSwatches' => $this->getShowSwatches(),
            'swatchesToShow' => $this->getNumberSwatchesPerProduct(),
            'priceFormat' => $this->getPriceFormat(),
            'serversideUrl' => $this->getServersideUrl()
        ];
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->serviceUrl->getClientScriptUrl($this->coreConfig->getAccessKey($this->getStoreId()));
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        $currency = '';
        try {
            $currency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        } catch (NoSuchEntityException $e) {
            $this->logger->error('PureClarity store error in getCurrencyCode:' . $e->getMessage());
        }
        return $currency;
    }

    /**
     * @return array
     */
    public function getProduct()
    {
        $productData = [];
        $product = $this->registry->registry('product');
        if ($product !== null) {
            $productData = [
                'Id' => $product->getId(),
                'Sku' => $product->getSku()
            ];
        }
        return $productData;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->coreConfig->isActive($this->getStoreId());
    }

    /**
     * @return bool
     */
    public function getMode()
    {
        return $this->coreConfig->getMode($this->getStoreId());
    }

    /**
     * @return bool
     */
    public function isLogOut()
    {
        return $this->request->getFullActionName() === 'customer_account_logoutSuccess';
    }

    /**
     * @return mixed[]
     */
    public function getPageContext()
    {
        $context = [];
        $route = $this->request->getFullActionName();

        if ($route === 'cms_index_index') {
            $context['page_type'] = 'homepage';
        } elseif ($route === 'cms_page_view') {
            $context['page_type'] = 'content_page';
        } elseif ($route === 'catalogsearch_result_index' || $route === 'catalogsearch_advanced_result') {
            $context['page_type'] = 'search_results';
        } elseif ($this->isCustomerAccountPage($route)) {
            $context['page_type'] = 'my_account';
        } elseif ($route === 'catalog_category_view') {
            $context = $this->getCategoryContext();
        } elseif ($route === 'catalog_product_view') {
            $context = $this->getProductContext();
        } elseif ($route === 'checkout_cart_index') {
            $context['page_type'] = 'basket_page';
        } elseif ($route === 'checkout_onepage_success') {
            $context['page_type'] = 'order_complete_page';
        }
        return $context;
    }

    /**
     * Checks the route to see if it can be considered an account page
     *
     * @param string $route - the route to check
     * @return bool
     */
    public function isCustomerAccountPage($route)
    {
        $accountRoutes = [
            'customer_account_index',
            'sales_order_history',
            'sales_order_view',
            'downloadable_customer_products',
            'wishlist_index_index',
            'customer_account_edit',
            'customer_address_index',
            'customer_address_edit',
            'vault_cards_listaction',
            'paypal_billing_agreement_index',
            'review_customer_index',
            'newsletter_manage_index'
        ];

        return in_array($route, $accountRoutes, true);
    }

    /**
     * @return array
     */
    public function getCategoryContext()
    {
        $context = [
            'page_type' => 'product_listing_page'
        ];

        $category = $this->registry->registry('current_category');
        if ($category !== null) {
            $context['category_id'] = $category->getId();
        }
        return $context;
    }

    /**
     * @return array
     */
    public function getProductContext()
    {
        $context = [
            'page_type' => 'product_page'
        ];

        $product = $this->registry->registry('product');
        if ($product !== null) {
            $context['product_id'] = $product->getId();
        }

        $category = $this->registry->registry('current_category');
        if ($category !== null) {
            $context['category_id'] = $category->getId();
        }

        return $context;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        $baseUrl = '';
        try {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        } catch (NoSuchEntityException $e) {
            $this->logger->error('PureClarity store error in getBaseUrl:' . $e->getMessage());
        }
        return $baseUrl;
    }

    /**
     * @return string
     */
    public function getWishlistUrl()
    {
        return $this->urlBuilder->getUrl('wishlist/index/add');
    }

    /**
     * @return string
     */
    public function getCompareUrl()
    {
        return $this->urlBuilder->getUrl('catalog/product_compare/add');
    }

    /**
     * @return int
     */
    public function getShowSwatches()
    {
        return (int)$this->coreConfig->showSwatches($this->getStoreId());
    }

    /**
     * @return string
     */
    public function getNumberSwatchesPerProduct()
    {
        return $this->coreConfig->getNumberSwatchesPerProduct($this->getStoreId());
    }

    /**
     * @return array
     */
    public function getPriceFormat()
    {
        return $this->format->getPriceFormat();
    }

    /**
     * @return string
     */
    public function getServersideUrl()
    {
        return $this->urlBuilder->getUrl('pureclarity/index/index');
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
                $this->storeId = $this->storeManager->getStore()->getId();
            } catch (NoSuchEntityException $e) {
                $this->storeId = 0;
            }
        }
        return $this->storeId;
    }
}
