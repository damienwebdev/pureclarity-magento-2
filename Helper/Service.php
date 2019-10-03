<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Helper\Service\Url;
use Pureclarity\Core\Model\CoreConfig;
use Zend\Http\Client;
use Zend\Json\Json;

/**
 * Class Service
 *
 * Helper class for core service functions.
 */
class Service
{
    private $action;
    private $zones = [];
    private $events = [];
    private $dispatched = false;
    private $result;
    private $isCategory = false;
    private $query = null;
    private $sort = null;
    private $size = null;

    /** @var LoggerInterface  */
    private $logger;

    /** @var Data $coreHelper */
    private $coreHelper;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var CookieManagerInterface $cookieManager */
    private $cookieManager;

    /** @var SessionManagerInterface $sessionManager */
    private $sessionManager;

    /** @var CookieMetadataFactory $cookieMetadataFactory */
    private $cookieMetadataFactory;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var Url $serviceUrl */
    private $serviceUrl;

    /**
     * @param LoggerInterface $logger
     * @param Data $coreHelper
     * @param StoreManagerInterface $storeManager
     * @param CookieManagerInterface $cookieManager
     * @param SessionManagerInterface $sessionManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CoreConfig $coreConfig
     * @param Url $serviceUrl
     */
    public function __construct(
        LoggerInterface $logger,
        Data $coreHelper,
        StoreManagerInterface $storeManager,
        CookieManagerInterface $cookieManager,
        SessionManagerInterface $sessionManager,
        CookieMetadataFactory $cookieMetadataFactory,
        CoreConfig $coreConfig,
        Url $serviceUrl
    ) {
        $this->logger                = $logger;
        $this->coreHelper            = $coreHelper;
        $this->storeManager          = $storeManager;
        $this->cookieManager         = $cookieManager;
        $this->sessionManager        = $sessionManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->coreConfig            = $coreConfig;
        $this->serviceUrl            = $serviceUrl;
    }

    public function setAction($action)
    {
        if ($action == 'customer_section_load') {
            return;
        }
        $this->action = $action;
    }

    public function addTrackingEvent($event, $data)
    {
        $newEvent = [
            "name" => $event
        ];
        if ($data) {
            $newEvent['data'] = $data;
        }
        $this->events[] = $newEvent;
    }

    public function dispatch($isMagentoAdminCall = false)
    {
        if ($this->dispatched ||
            (!$this->action && !$isMagentoAdminCall)) {
            return;
        }
        
        $this->dispatched = true;

        // Set up Request
        $storeId = $this->coreHelper->getStoreId();

        // Insert page_view as default
        if (!$isMagentoAdminCall) {
            array_unshift($this->events, ["name" => "page_view"]);
        }

        $requestBody = [
            "appId" => $this->coreConfig->getAccessKey($storeId),
            "secretKey" => $this->coreConfig->getSecretKey($storeId),
            "events" => $this->events,
            "zones" => $this->zones
        ];
        
        // Set up search
        if ($this->query) {
            $requestBody['search'] = [
                "query" => $this->query,
                "requiredAttributes" => ["Id", "Sku"]
            ];
            if ($this->size) {
                $requestBody['search']["pageSize"] = $this->size;
            }
            if ($this->sort) {
                $requestBody['search']['sort'] = $this->sort;
            }
            if ($this->isCategory) {
                $requestBody['search']['isCategory'] = true;
            }
        }
        
        if ($isMagentoAdminCall) {
            $requestBody['currentUrl'] = 'magento-admin';
        } else {
            $requestBody['currentUrl'] = $this->coreHelper->getCurrentUrl();
            $requestBody['currency'] =  $this->storeManager->getStore()->getCurrentCurrency()->getCode();

            if (array_key_exists('HTTP_REFERER', $_SERVER)) {
                $requestBody['referer'] = $_SERVER['HTTP_REFERER'];
            }
            
            if (array_key_exists('pc_v', $_COOKIE)) {
                $requestBody['visitorId']=$_COOKIE['pc_v'];
            }

            if (array_key_exists('pc_sessid', $_COOKIE)) {
                $requestBody['sessionId']=$_COOKIE['pc_sessid'];
            }
        }

        if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
            $requestBody['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
        }
    
        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $requestBody['ip'] = $_SERVER['REMOTE_ADDR'];
        }

        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $requestBody['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $this->executeRequest($storeId, $requestBody);
    }

    public function executeRequest($storeId, $requestBody)
    {
        // Set Url
        $url = $this->serviceUrl->getServerSideEndpoint($this->coreConfig->getRegion($storeId));
        
        // Build request
        $client = new Client($url);
        $client
            ->setHeaders([
                'Content-Type' => 'application/json',
            ])
            ->setOptions([
                    'sslverifypeer' => $this->coreHelper->useSSL($storeId),
                    'adapter'   => 'Zend\Http\Client\Adapter\Curl',
                    'curloptions' => [CURLOPT_FOLLOWLOCATION => true],
                    'maxredirects' => 0,
                    'timeout' => 30
                ])
            ->setMethod('POST')
            ->setRawBody(Json::encode($requestBody));
        
        try {
            $response = $client->send();
            $this->result = json_decode($response->getBody(), true);
        
            if (array_key_exists('errors', $this->result)) {
                $this->logger->error(
                    'PURECLARITY ERROR: Errors return from PureClarity - ' . var_export($this->result['errors'], true)
                );
                return;
            }
        
            if (array_key_exists('visitorId', $this->result)) {
                $this->setCookie('pc_v', $this->result['visitorId'], 3122064000);
            }
            if (array_key_exists('sessionId', $this->result)) {
                $this->setCookie('pc_sessid', $this->result['sessionId'], 300);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'PURECLARITY ERROR: There was a problem communicating with the PureClarity Endpoint: '
                . $e->getMessage()
            );
        }
    }

    protected function setCookie($cookieName, $value, $duration)
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath($this->sessionManager->getCookiePath());
            //->setDomain($this->sessionManager->getCookieDomain());

        $this->cookieManager->setPublicCookie(
            $cookieName,
            $value,
            $metadata
        );
    }
}
