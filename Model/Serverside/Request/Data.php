<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Pureclarity\Core\Model\Serverside\Request;

/**
 * Serverside Request class. Holds all the information for a serverside request
 */
class Data
{
    /** @var string */
    const KEY_APPID = 'appId';

    /** @var string */
    const KEY_SECRET_KEY = 'secretKey';

    /** @var string */
    const KEY_EVENTS = 'events';

    /** @var string */
    const KEY_ZONES = 'zones';

    /** @var string */
    const KEY_REFERER = 'referer';

    /** @var string */
    const KEY_CURRENT_URL = 'currentUrl';

    /** @var string */
    const KEY_CURRENCY = 'currency';

    /** @var string */
    const KEY_VISITOR_ID = 'visitorId';

    /** @var string */
    const KEY_SESSION_ID = 'sessionId';

    /** @var string */
    const KEY_USER_AGENT = 'userAgent';

    /** @var string */
    const KEY_IP = 'ip';

    /** @var string */
    const KEY_SEARCH_TERM = 'searchterm';

    /** @var mixed[] */
    private $requestData;

    /**
     * @param string $value
     */
    public function setAppId($value)
    {
        $this->requestData[self::KEY_APPID] = $value;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return isset($this->requestData[self::KEY_APPID]) ? $this->requestData[self::KEY_APPID] : '';
    }

    /**
     * @param string $value
     */
    public function setSecretKey($value)
    {
        $this->requestData[self::KEY_SECRET_KEY] = $value;
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return isset($this->requestData[self::KEY_SECRET_KEY]) ? $this->requestData[self::KEY_SECRET_KEY] : '';
    }

    /**
     * @param $event
     * @param mixed[] $data
     */
    public function addEvent($event, $data = [])
    {
        $newEvent = [
            'name' => $event,
            'data' => $data
        ];

        $this->requestData[self::KEY_EVENTS][] = $newEvent;
    }

    /**
     * @return mixed[]
     */
    public function getEvents()
    {
        return isset($this->requestData[self::KEY_EVENTS]) ? $this->requestData[self::KEY_EVENTS] : [];
    }

    /**
     * @param string $zoneId
     * @param mixed[] $data
     */
    public function addZone($zoneId, $data = [])
    {
        $zone = [ 'id' => $zoneId ];

        if (!empty($data)) {
            $zone['data'] = $data;
        }

        $this->requestData[self::KEY_ZONES][] = $zone;
    }

    /**
     * @return mixed[]
     */
    public function getZones()
    {
        return isset($this->requestData[self::KEY_ZONES]) ? $this->requestData[self::KEY_ZONES] : [];
    }

    /**
     * @param string $value
     */
    public function setReferer($value)
    {
        $this->requestData[self::KEY_REFERER] = $value;
    }

    /**
     * @return string
     */
    public function getReferer()
    {
        return isset($this->requestData[self::KEY_REFERER]) ? $this->requestData[self::KEY_REFERER] : '';
    }

    /**
     * @param string $value
     */
    public function setCurrentUrl($value)
    {
        $this->requestData[self::KEY_CURRENT_URL] = $value;
    }

    /**
     * @return string
     */
    public function getCurrentUrl()
    {
        return isset($this->requestData[self::KEY_CURRENT_URL]) ? $this->requestData[self::KEY_CURRENT_URL] : '';
    }

    /**
     * @param string $value
     */
    public function setCurrency($value)
    {
        $this->requestData[self::KEY_CURRENCY] = $value;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return isset($this->requestData[self::KEY_CURRENCY]) ? $this->requestData[self::KEY_CURRENCY] : '';
    }

    /**
     * @param string $value
     */
    public function setVisitorId($value)
    {
        $this->requestData[self::KEY_VISITOR_ID] = $value;
    }

    /**
     * @return string
     */
    public function getVisitorId()
    {
        return isset($this->requestData[self::KEY_VISITOR_ID]) ? $this->requestData[self::KEY_VISITOR_ID] : '';
    }

    /**
     * @param string $value
     */
    public function setSessionId($value)
    {
        $this->requestData[self::KEY_SESSION_ID] = $value;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return isset($this->requestData[self::KEY_SESSION_ID]) ? $this->requestData[self::KEY_SESSION_ID] : '';
    }

    /**
     * @param string $value
     */
    public function setUserAgent($value)
    {
        $this->requestData[self::KEY_USER_AGENT] = $value;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return isset($this->requestData[self::KEY_USER_AGENT]) ? $this->requestData[self::KEY_USER_AGENT] : '';
    }

    /**
     * @param string $value
     */
    public function setIp($value)
    {
        $this->requestData[self::KEY_IP] = $value;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return isset($this->requestData[self::KEY_IP]) ? $this->requestData[self::KEY_IP] : '';
    }

    /**
     * @param string $value
     */
    public function setSearchTerm($value)
    {
        $this->requestData[self::KEY_SEARCH_TERM] = $value;
    }

    /**
     * @return string
     */
    public function getSearchTerm()
    {
        return isset($this->requestData[self::KEY_SEARCH_TERM]) ? $this->requestData[self::KEY_SEARCH_TERM] : '';
    }
}
