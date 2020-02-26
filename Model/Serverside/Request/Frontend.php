<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Pureclarity\Core\Model\Serverside\Request;

use Pureclarity\Core\Helper\Serverside as ServersideHelper;
use Pureclarity\Core\Model\Serverside\Response\ProductData;
use Pureclarity\Core\Model\Serverside\Data\Cookie;
use Pureclarity\Core\Model\Serverside\Data\General;
use Pureclarity\Core\Model\Serverside\Data\Store;
use Pureclarity\Core\Model\Serverside\Data\Cart;
use Pureclarity\Core\Model\Serverside\Data\Customer;

/**
 * Serverside Frontend request handler model, generates and sends a serverside request in Frontend context
 */
class Frontend
{
    /** @var Data */
    private $request;

    /** @var DataFactory */
    private $requestFactory;

    /** @var ServersideHelper */
    private $serverSide;

    /** @var ProductData */
    private $productData;

    /** @var Cookie */
    private $cookie;

    /** @var Cart */
    private $cart;

    /** @var General */
    private $general;

    /** @var Store */
    private $store;

    /** @var Customer */
    private $customer;

    /** @var string */
    private $storeId;

    /**
     * @param DataFactory $requestFactory
     * @param ServersideHelper $serverSide
     * @param ProductData $productData
     * @param Cookie $cookie
     * @param General $general
     * @param Store $store
     * @param Cart $cart
     * @param Customer $customer
     */
    public function __construct(
        DataFactory $requestFactory,
        ServersideHelper $serverSide,
        ProductData $productData,
        Cookie $cookie,
        General $general,
        Store $store,
        Cart $cart,
        Customer $customer
    ) {
        $this->requestFactory = $requestFactory;
        $this->serverSide     = $serverSide;
        $this->productData    = $productData;
        $this->cookie         = $cookie;
        $this->general        = $general;
        $this->store          = $store;
        $this->cart           = $cart;
        $this->customer       = $customer;
    }

    /**
     * @param string $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Builds and sends a serverside request
     *
     * @param mixed[] $params
     * @return mixed[]
     */
    public function execute($params)
    {
        $request = $this->buildRequest($params);
        $this->serverSide->dispatch($request);
        return $this->processResult($request, $this->storeId);
    }

    /**
     * Builds the request information into the request object
     *
     * @param mixed[] $params
     * @return Data
     */
    public function buildRequest($params)
    {
        /** @var Data $request */
        $this->request = $this->requestFactory->create();

        $this->addGeneralInfo($this->storeId, $params);
        $this->addZones($params);
        $this->addPageView($params);
        $this->addProductView($params);
        $this->addCustomerDetails($this->storeId);
        $this->addSetBasket();
        $this->addOrder($params);

        return $this->request;
    }

    /**
     * Adds the General information to the Request (App info, urls, currency etc)
     *
     * @param string $storeId
     * @param mixed[] $params
     */
    public function addGeneralInfo($storeId, $params)
    {
        $this->serverSide->setStoreId($storeId);

        $this->request->setAppId($this->store->getAccessKey($storeId));
        $this->request->setSecretKey($this->store->getSecretKey($storeId));
        $this->request->setUserAgent($this->general->getUserAgent());
        $this->request->setIp($this->general->getIp());
        $this->request->setCurrency($this->store->getCurrency($storeId));
        $this->request->setCurrentUrl(isset($params['current_url']) ? $params['current_url'] : '');
        $this->request->setReferer(isset($params['referer']) ? $params['referer'] : '');
    }

    /**
     * Adds specified Zones to the request
     *
     * @param mixed[] $params
     */
    public function addZones($params)
    {
        if (isset($params['zones'])) {
            foreach ($params['zones'] as $zone) {
                $this->request->addZone($zone, []);
            }
        }
    }

    /**
     * Adds Page View event to the request
     *
     * @param mixed[] $params
     */
    public function addPageView($params)
    {
        $this->request->addEvent(
            'page_view',
            isset($params['page']) ? $params['page'] : []
        );
    }

    /**
     * Adds Product View event to the request
     *
     * @param mixed[] $params
     */
    public function addProductView($params)
    {
        if (isset($params['product']) && !empty($params['product'])) {
            $this->request->addEvent(
                'product_view',
                ['id' => $params['product']['Id']]
            );
        }
    }

    /**
     * Adds Customer details event to the request (if needed)
     * @param integer $storeId
     */
    public function addCustomerDetails($storeId)
    {
        $logout = false;
        $customerDetailsCheck = $this->customer->checkCustomer();
        if ($customerDetailsCheck['send']) {
            if (!empty($customerDetailsCheck['details'])) {
                $this->request->addEvent(
                    'customer_details',
                    $customerDetailsCheck['details']
                );
            } else {
                $this->cookie->customerLogout($storeId);
                $logout = true;
            }
        }

        if ($logout === false) {
            $pcv = $this->cookie->getCookie('pc_v_', $storeId);
            if ($pcv) {
                $this->request->setVisitorId($pcv);
            }

            $pcSessid = $this->cookie->getCookie('pc_sessid_', $storeId);
            if ($pcSessid) {
                $this->request->setSessionId($pcSessid);
            }
        }
    }

    /**
     * Adds the set basket event to the request (if needed)
     */
    public function addSetBasket()
    {
        $cartCheck = $this->cart->checkCart();
        if ($cartCheck['send']) {
            $this->request->addEvent(
                'set_basket',
                $cartCheck['items']
            );
        }
    }

    /**
     * Adds the order event if needed
     * @param mixed[] $params
     */
    public function addOrder($params)
    {
        if (isset($params['order']) && !empty($params['order'])) {
            $this->request->addEvent('order_track', $params['order']);
        }
    }

    /**
     * Processes the result from PureClarity
     *
     * Renders Zones and updates cookies
     *
     * @param Data $request
     * @param string $storeId
     * @return mixed[]
     */
    public function processResult($request, $storeId)
    {
        $result = $this->serverSide->getResult();

        if (isset($result['zones'])) {
            $this->productData->setCurrentUrl($request->getCurrentUrl());
            foreach ($result['zones'] as $zoneId => $zone) {
                $result['zones'][$zoneId]['items'] = $this->productData->getProductData($zone);
                unset($result['zones'][$zoneId]['data']['items']);
            }
        }

        if (isset($result['visitorId'])) {
            $this->cookie->setCookie('pc_v_', $result['visitorId'], 3122064000, $storeId);
        }

        if (isset($result['sessionId'])) {
            $this->cookie->setCookie('pc_sessid_', $result['sessionId'], 300, $storeId);
        }

        return $result;
    }
}
