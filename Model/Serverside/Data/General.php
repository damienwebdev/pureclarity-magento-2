<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Serverside\Data;

use Magento\Framework\HTTP\Header;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

/**
 * Serverside General information handler, gets general information for the serverside request
 */
class General
{
    /** @var Header */
    private $httpHeader;

    /** @var RemoteAddress */
    private $remoteIp;

    /**
     * @param Header $httpHeader
     * @param RemoteAddress $remoteIp
     */
    public function __construct(
        Header $httpHeader,
        RemoteAddress $remoteIp
    ) {
        $this->httpHeader   = $httpHeader;
        $this->remoteIp     = $remoteIp;
    }

    /**
     * Gets the Remote IP address
     *
     * @return string
     */
    public function getIp()
    {
        return $this->remoteIp->getRemoteAddress();
    }

    /**
     * Gets the HTTP User Agent
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->httpHeader->getHttpUserAgent();
    }
}
