<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Serverside\Data;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Serverside Cookie handler, gets & sets PureClarity cookie data
 */
class Cookie
{
    /** @var LoggerInterface */
    private $logger;

    /** @var SessionManagerInterface */
    private $sessionManager;

    /** @var CookieManagerInterface */
    private $cookieManager;

    /** @var CookieMetadataFactory */
    private $cookieMetadataFactory;

    /** @var CoreConfig */
    private $coreConfig;

    /**
     * @param LoggerInterface $logger
     * @param CookieManagerInterface $cookieManager
     * @param SessionManagerInterface $sessionManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        LoggerInterface $logger,
        CookieManagerInterface $cookieManager,
        SessionManagerInterface $sessionManager,
        CookieMetadataFactory $cookieMetadataFactory,
        CoreConfig $coreConfig
    ) {
        $this->logger                = $logger;
        $this->cookieManager         = $cookieManager;
        $this->sessionManager        = $sessionManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->coreConfig            = $coreConfig;
    }

    /**
     * Clears the customer PureClarity cookies
     *
     * @param string $storeId
     */
    public function customerLogout($storeId)
    {
        try {
            $this->setCookie('pc_v_', '', 3122064000, $storeId);
            $this->setCookie('pc_sessid_', '', 300, $storeId);
        } catch (\Exception $e) {
            $this->logger->error('PURECLARITY COOKIE (customerLogout) ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Gets the provided cookie
     *
     * @param string $cookieName
     * @param string $storeId
     * @return string|null
     */
    public function getCookie($cookieName, $storeId)
    {
        $value = '';
        try {
            $appId = $this->coreConfig->getAccessKey($storeId);
            $value = $this->cookieManager->getCookie($cookieName . $appId);
        } catch (\Exception $e) {
            $this->logger->error('PURECLARITY COOKIE (getCookie) ERROR: ' . $e->getMessage());
        }
        return $value;
    }

    /**
     * Sets the value in the provided cookie
     *
     * @param string $cookieName
     * @param string $value
     * @param string $duration
     * @param string $storeId
     */
    public function setCookie($cookieName, $value, $duration, $storeId)
    {
        try {
            $appId = $this->coreConfig->getAccessKey($storeId);
            $cookieName .= $appId;

            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration($duration)
                ->setPath($this->sessionManager->getCookiePath());

            $this->cookieManager->setPublicCookie(
                $cookieName,
                $value,
                $metadata
            );
        } catch (\Exception $e) {
            $this->logger->error('PURECLARITY COOKIE (setCookie) ERROR: ' . $e->getMessage());
        }
    }
}
