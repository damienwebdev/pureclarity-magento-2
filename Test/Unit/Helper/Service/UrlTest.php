<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Helper\Service;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Helper\Service\Url;

/**
 * Class UrlTest
 *
 * Tests the methods in \Pureclarity\Core\Helper\Service\Url
 */
class UrlTest extends TestCase
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

    /** @var Url $object */
    private $object;

    protected function setUp()
    {
        $this->object = new Url();
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testStoreDataInstance()
    {
        $this->assertInstanceOf(Url::class, $this->object);
    }

    /**
     * Tests that the admin url is returned correctly
     */
    public function testGetAdminUrl()
    {
        $this->assertEquals('https://admin.pureclarity.com', $this->object->getAdminUrl());
    }

    /**
     * Tests that the github url is returned correctly
     */
    public function testGetGithubUrl()
    {
        $this->assertEquals(
            'https://api.github.com/repos/pureclarity/pureclarity-magento-2/releases/latest',
            $this->object->getGithubUrl()
        );
    }

    /**
     * Tests that the Delta endpoint is returned correctly - with env variable set to override the real value
     */
    public function testGetDeltaEndpoint()
    {
        $localUrl = 'http://127.0.0.1';
        putenv('PURECLARITY_HOST=' . $localUrl);

        foreach (array_keys($this->regions) as $regionId) {
            $this->assertEquals($localUrl . '/api/productdelta', $this->object->getDeltaEndpoint($regionId));
        }
    }

    /**
     * Tests that the Delta endpoint is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetDeltaEndpointReal()
    {
        putenv('PURECLARITY_HOST=');

        foreach ($this->regions as $regionId => $url) {
            $this->assertEquals($url . '/api/productdelta', $this->object->getDeltaEndpoint($regionId));
        }
    }

    /**
     * Tests that the signup request endpoint is returned correctly - with env variable set to override the real value
     */
    public function testGetSignupRequestEndpointUrl()
    {
        $localUrl = 'http://127.0.0.1';
        putenv('PURECLARITY_HOST=' . $localUrl);

        foreach (array_keys($this->regions) as $regionId) {
            $this->assertEquals(
                $localUrl . '/api/plugin/signuprequest',
                $this->object->getSignupRequestEndpointUrl($regionId)
            );
        }
    }

    /**
     * Tests that the signup request endpoint is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetSignupRequestEndpointUrlReal()
    {
        putenv('PURECLARITY_HOST=');
        foreach ($this->regions as $regionId => $url) {
            $this->assertEquals(
                $url . '/api/plugin/signuprequest',
                $this->object->getSignupRequestEndpointUrl($regionId)
            );
        }
    }

    /**
     * Tests that the signup status endpoint is returned correctly - with env variable set to override the real value
     */
    public function testGetSignupStatusEndpointUrl()
    {
        $localUrl = 'http://127.0.0.1';
        putenv('PURECLARITY_HOST=' . $localUrl);

        foreach (array_keys($this->regions) as $regionId) {
            $this->assertEquals(
                $localUrl . '/api/plugin/signupstatus',
                $this->object->getSignupStatusEndpointUrl($regionId)
            );
        }
    }

    /**
     * Tests that the signup status endpoint is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetSignupStatusEndpointUrlReal()
    {
        putenv('PURECLARITY_HOST=');
        foreach ($this->regions as $regionId => $url) {
            $this->assertEquals(
                $url . '/api/plugin/signupstatus',
                $this->object->getSignupStatusEndpointUrl($regionId)
            );
        }
    }

    /**
     * Tests that the sftp endpoint is returned correctly - with env variable set to override the real value
     */
    public function testGetFeedSftpUrl()
    {
        $localUrl = 'http://127.0.0.1';
        putenv('PURECLARITY_FEED_HOST=' . $localUrl);
        putenv('PURECLARITY_FEED_PORT=1234');
        foreach (array_keys($this->regions) as $regionId) {
            $this->assertEquals(
                $localUrl . ':1234/',
                $this->object->getFeedSftpUrl($regionId)
            );
        }
    }

    /**
     * Tests that the sftp endpoint is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetFeedSftpUrlReal()
    {
        putenv('PURECLARITY_FEED_HOST=');
        putenv('PURECLARITY_FEED_PORT=');
        foreach ($this->sftpRegions as $regionId => $url) {
            $this->assertEquals(
                $url . '/',
                $this->object->getFeedSftpUrl($regionId)
            );
        }
    }

    /**
     * Tests that the client script base url is returned correctly
     */
    public function testGetClientScriptBaseUrl()
    {
        $this->assertEquals($this->scriptUrl, $this->object->getClientScriptBaseUrl());
    }

    /**
     * Tests that the client script url is returned correctly - with env variable set to override the real value
     */
    public function testGetClientScriptUrl()
    {
        $localUrl = 'http://127.0.0.1/';
        putenv('PURECLARITY_SCRIPT_URL=' . $localUrl);
        $this->assertEquals(
            $localUrl . 'ACCESSKEY1234/cs.js',
            $this->object->getClientScriptUrl('ACCESSKEY1234')
        );
    }

    /**
     * Tests that the client script url is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetClientScriptUrlReal()
    {
        putenv('PURECLARITY_SCRIPT_URL=');
        $this->assertEquals(
            $this->scriptUrl . '/ACCESSKEY1234/cs.js',
            $this->object->getClientScriptUrl('ACCESSKEY1234')
        );
    }

    /**
     * Tests that the serverside endpoint is returned correctly - with env variable set to override the real value
     */
    public function testGetServerSideEndpoint()
    {
        $localUrl = 'http://127.0.0.1';
        putenv('PURECLARITY_HOST=' . $localUrl);
        foreach (array_keys($this->regions) as $regionId) {
            $this->assertEquals(
                $localUrl . '/api/serverside',
                $this->object->getServerSideEndpoint($regionId)
            );
        }
    }

    /**
     * Tests that the serverside endpoint is returned correctly - with env set to empty so it returns a real value
     */
    public function testGetServerSideEndpointReal()
    {
        putenv('PURECLARITY_HOST=');
        foreach ($this->regions as $regionId => $url) {
            $this->assertEquals(
                $url . '/api/serverside',
                $this->object->getServerSideEndpoint($regionId)
            );
        }
    }
}
