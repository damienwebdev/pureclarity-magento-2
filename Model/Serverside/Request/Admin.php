<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Pureclarity\Core\Model\Serverside\Request;

use Pureclarity\Core\Model\Serverside\Request\DataFactory;
use Pureclarity\Core\Helper\Serverside as ServersideHelper;
use Pureclarity\Core\Model\Serverside\Data\General;
use Pureclarity\Core\Model\Serverside\Data\Store;

/**
 * Serverside Admin request handler model, generates and sends a serverside request in Admin context
 */
class Admin
{
    /** @var Data */
    private $request;

    /** @var DataFactory */
    private $requestFactory;

    /** @var ServersideHelper */
    private $serverSide;

    /** @var General */
    private $general;

    /** @var Store */
    private $store;

    /** @var string */
    private $storeId;

    /**
     * @param DataFactory $requestFactory
     * @param ServersideHelper $serverSide
     * @param General $general
     * @param Store $store
     */
    public function __construct(
        DataFactory $requestFactory,
        ServersideHelper $serverSide,
        General $general,
        Store $store
    ) {
        $this->requestFactory = $requestFactory;
        $this->serverSide     = $serverSide;
        $this->general        = $general;
        $this->store          = $store;
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
     */
    public function execute($params)
    {
        $request = $this->buildRequest($params);
        $this->serverSide->dispatch($request);
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

        $this->addGeneralInfo($this->storeId);
        $this->addMotoOrder($params);

        return $this->request;
    }

    /**
     * Adds the General information to the Request (App info, urls, currency etc)
     *
     * @param string $storeId
     * @param mixed[] $params
     */
    public function addGeneralInfo($storeId)
    {
        $this->serverSide->setStoreId($storeId);
        $this->serverSide->setIsAdmin(true);

        $this->request->setAppId($this->store->getAccessKey($storeId));
        $this->request->setSecretKey($this->store->getSecretKey($storeId));
        $this->request->setIp($this->general->getIp());
        $this->request->setCurrency($this->store->getCurrency($storeId));
    }

    /**
     * Adds the order event if needed
     * @param mixed[] $params
     */
    public function addMotoOrder($params)
    {
        if (isset($params['moto_order']) && !empty($params['moto_order'])) {
            $this->request->addEvent('moto_order_track', $params['moto_order']);
        }
    }
}
