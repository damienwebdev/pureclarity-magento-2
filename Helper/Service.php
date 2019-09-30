<?php

namespace Pureclarity\Core\Helper;

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Json\Json;

/**
 * Helper class for core service functions.
 */

class Service extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $logger;
    protected $registry;
    protected $coreHelper;
    protected $storeManager;
    protected $cookieManager;
    protected $sessionManager;
    protected $checkoutSession;
    protected $productCollection;
    protected $action;
    protected $zones = [];
    protected $events = [];
    protected $dispatched = false;
    protected $result;
    protected $isCategory = false;
    protected $category;
    protected $query = null;
    protected $sort = null;
    protected $size = null;
    protected $catalogSearchHelper;
    protected $toolBar;
    protected $request;
    protected $responseFactory;
    
    /** @var \Pureclarity\Core\Helper\Service\CustomerDetails */
    private $customerDetails;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\CatalogSearch\Helper\Data $catalogSearchHelper,
        \Magento\Catalog\Model\Product\ProductList\Toolbar $toolBar,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Pureclarity\Core\Helper\Service\CustomerDetails $customerDetails
    ) {
        $this->logger = $context->getLogger();
        $this->registry = $registry;
        $this->coreHelper = $coreHelper;
        $this->storeManager = $storeManager;
        $this->cookieManager = $cookieManager;
        $this->sessionManager = $sessionManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->checkoutSession = $checkoutSession;
        $this->productCollection = $productCollection;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->toolBar = $toolBar;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->customerDetails = $customerDetails;

        $this->category = $this->registry->registry('current_category');
        parent::__construct($context);
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
            (!$this->action && !$isMagentoAdminCall) ||
            (!$this->coreHelper->seoSearchFriendly() && !$isMagentoAdminCall)) {
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
            "appId" => $this->coreHelper->getAccessKey($storeId),
            "secretKey" => $this->coreHelper->getSecretKey($storeId),
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
        $url = $this->coreHelper->getServerSideEndpoint($storeId);
        
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
