<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper\Service;

/**
 * Class URL
 *
 * Helper class for URL related functions
 */
class Url
{
    /**
     * Default PureClarity ClientScript URL
     *
     * @var string
     */
    private $scriptUrl = '//pcs.pureclarity.net';

    /**
     * Default PureClarity ClientScript Region URLS
     *
     * @var string[]
     */
    private $regions = [
        1 => "https://api-eu-w-1.pureclarity.net",
        2 => "https://api-eu-w-2.pureclarity.net",
        3 => "https://api-eu-c-1.pureclarity.net",
        4 => "https://api-us-e-1.pureclarity.net",
        5 => "https://api-us-e-2.pureclarity.net",
        6 => "https://api-us-w-1.pureclarity.net",
        7 => "https://api-us-w-2.pureclarity.net",
        8 => "https://api-ap-s-1.pureclarity.net",
        9 => "https://api-ap-ne-1.pureclarity.net",
        10 => "https://api-ap-ne-2.pureclarity.net",
        11 => "https://api-ap-se-1.pureclarity.net",
        12 => "https://api-ap-se-2.pureclarity.net",
        13 => "https://api-ca-c-1.pureclarity.net",
        14 => "https://api-sa-e-1.pureclarity.net"
    ];

    /**
     * Default PureClarity ClientScript Region SFTP endpoints
     *
     * @var string[]
     */
    private $sftpRegions = [
        1 => "https://sftp-eu-w-1.pureclarity.net",
        2 => "https://sftp-eu-w-2.pureclarity.net",
        3 => "https://sftp-eu-c-1.pureclarity.net",
        4 => "https://sftp-us-e-1.pureclarity.net",
        5 => "https://sftp-us-e-2.pureclarity.net",
        6 => "https://sftp-us-w-1.pureclarity.net",
        7 => "https://sftp-us-w-2.pureclarity.net",
        8 => "https://sftp-ap-s-1.pureclarity.net",
        9 => "https://sftp-ap-ne-1.pureclarity.net",
        10 => "https://sftp-ap-ne-2.pureclarity.net",
        11 => "https://sftp-ap-se-1.pureclarity.net",
        12 => "https://sftp-ap-se-2.pureclarity.net",
        13 => "https://sftp-ca-c-1.pureclarity.net",
        14 => "https://sftp-sa-e-1.pureclarity.net"
    ];

    /**
     * Gets the PureClarity Admin URL
     *
     * @return string
     */
    public function getAdminUrl()
    {
        return "https://admin.pureclarity.com";
    }

    /**
     * Gets the PureClarity Github API url
     *
     * @return string
     */
    public function getGithubUrl()
    {
        return "https://api.github.com/repos/pureclarity/pureclarity-magento-2/releases/latest";
    }

    /**
     * Gets the PureClarity delta endpoint for the given store
     *
     * @param integer $region
     * @return string
     */
    public function getDeltaEndpoint($region)
    {
        return $this->getHost($region) . '/api/productdelta';
    }

    /**
     * Gets the PureClarity signup request endpoint for the given region
     *
     * @param integer $region
     * @return string
     */
    public function getSignupRequestEndpointUrl($region)
    {
        return $this->getHost($region) . '/api/plugin/signuprequest';
    }

    /**
     * Gets the PureClarity signup tracking endpoint for the given region
     *
     * @param integer $region
     * @return string
     */
    public function getSignupStatusEndpointUrl($region)
    {
        return $this->getHost($region) . '/api/plugin/signupstatus';
    }

    /**
     * Gets the PureClarity SFTP base url for feeds for the given store
     *
     * @param integer $region
     * @return string
     */
    public function getFeedSftpUrl($region)
    {
        $url = getenv('PURECLARITY_FEED_HOST');
        $port = getenv('PURECLARITY_FEED_PORT');
        if (empty($url)) {
            $url = $this->sftpRegions[$region];
        }
        if (! empty($port)) {
            $url = $url . ":" . $port;
        }

        return $url . "/";
    }

    /**
     * Gets the default PureClarity clientscript base URL
     *
     * @return string
     */
    public function getClientScriptBaseUrl()
    {
        return $this->scriptUrl;
    }

    /**
     * Gets the PureClarity clientscript URL
     *
     * @param string $accessKey
     *
     * @return string
     */
    public function getClientScriptUrl($accessKey)
    {
        $pureclarityScriptUrl = getenv('PURECLARITY_SCRIPT_URL');
        if ($pureclarityScriptUrl != null && $pureclarityScriptUrl != '') {
            $pureclarityScriptUrl .= $accessKey . '/dev.js';
            return $pureclarityScriptUrl;
        }
        return $this->getClientScriptBaseUrl() . '/' . $accessKey . '/cs.js';
    }

    public function getServerSideEndpoint($region)
    {
        return $this->getHost($region) . '/api/serverside';
    }

    /**
     * Gets the PureClarity host endpoint for the given store
     *
     * @param integer $region
     * @return string
     */
    private function getHost($region)
    {
        $pureclarityHostEnv = getenv('PURECLARITY_MAGENTO_HOST');
        if ($pureclarityHostEnv != null && $pureclarityHostEnv != '') {
            $parsed = parse_url($pureclarityHostEnv);
            if (empty($parsed['scheme'])) {
                $pureclarityHostEnv = 'http://' . $pureclarityHostEnv;
            }
            return $pureclarityHostEnv;
        }

        return $this->regions[$region];
    }
}
