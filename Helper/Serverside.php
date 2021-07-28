<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper;

use Psr\Log\LoggerInterface;
use Pureclarity\Core\Helper\Service\Url;
use Magento\Framework\HTTP\Client\Curl;
use Pureclarity\Core\Helper\Data as CoreHelper;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Serverside\Request\Data;

/**
 * Helper class for core service functions.
 */
class Serverside
{
    /** @var LoggerInterface */
    private $logger;

    /** @var CoreHelper */
    private $coreHelper;

    /** @var Url */
    private $serviceUrl;

    /** @var Curl */
    private $curl;

    /** @var Serializer */
    private $serializer;

    /** @var CoreConfig */
    private $coreConfig;

    /** @var bool */
    private $dispatched = false;

    /** @var mixed[] */
    private $result;

    /** @var bool */
    private $isAdmin = false;

    /** @var string */
    private $storeId;

    /**
     * @param LoggerInterface $logger
     * @param CoreHelper $coreHelper
     * @param Url $serviceUrl
     * @param Curl $curl
     * @param Serializer $serializer
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        LoggerInterface $logger,
        CoreHelper $coreHelper,
        Url $serviceUrl,
        Curl $curl,
        Serializer $serializer,
        CoreConfig $coreConfig
    ) {
        $this->logger       = $logger;
        $this->coreHelper   = $coreHelper;
        $this->serviceUrl   = $serviceUrl;
        $this->curl         = $curl;
        $this->serializer   = $serializer;
        $this->coreConfig   = $coreConfig;
    }

    /**
     * @param bool $isAdmin
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * @param string $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Builds the request array and executes the request
     *
     * @param Data $request
     */
    public function dispatch($request)
    {
        if ($this->dispatched) {
            return;
        }

        $this->dispatched = true;

        $requestBody = [
            'appId' => $request->getAppId(),
            'secretKey' => $request->getSecretKey(),
            'events' => $request->getEvents(),
            'zones' => $request->getZones()
        ];

        if ($this->isAdmin) {
            $requestBody['currentUrl'] = 'magento-admin';
        } else {
            $requestBody['referer'] = $request->getReferer();
            $requestBody['currentUrl'] = $request->getCurrentUrl() ?: 'magento-backend';
            $requestBody['currency'] = $request->getCurrency();
            $requestBody['visitorId'] = $request->getVisitorId();
            $requestBody['sessionId'] = $request->getSessionId();
        }

        $requestBody['userAgent'] = $request->getUserAgent() ?: 'magento-backend';

        if ($ip = $request->getIp()) {
            $requestBody['ip'] = $ip;
        }

        if ($searchTerm = $request->getSearchTerm()) {
            $requestBody['searchterm'] = $searchTerm;
        }

        $this->logger->debug('Serverside: Request Sent - ' . var_export($requestBody, true));

        $this->executeRequest($requestBody);
    }

    /**
     * Sends the serverside request to the PureClarity server
     *
     * @param mixed[] $requestBody
     */
    public function executeRequest($requestBody)
    {
        $url = $this->serviceUrl->getServerSideEndpoint(
            $this->coreConfig->getRegion($this->storeId)
        );

        $this->logger->debug('Serverside: Sending request to ' . $url);

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
        ]);

        $this->curl->setOptions([
            CURLOPT_SSL_VERIFYPEER => $this->coreHelper->useSSL($this->storeId),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_TIMEOUT => 5
        ]);

        try {
            $this->curl->post($url, $this->serializer->serialize($requestBody));
            $this->result = $this->serializer->unserialize($this->curl->getBody());

            $this->logger->debug('Serverside: Response - ' . var_export($this->result, true));

            if (isset($this->result['errors']) && !empty($this->result['errors'])) {
                $this->logger->error(
                    'PURECLARITY ERROR: Errors return from PureClarity - ' . implode('|', $this->result['errors'])
                );
                return;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'PURECLARITY ERROR: There was a problem communicating with the PureClarity Endpoint: '
                . $e->getMessage()
            );
        }
    }

    /**
     * @return mixed[]
     */
    public function getResult()
    {
        return $this->result;
    }
}
