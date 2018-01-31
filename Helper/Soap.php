<?php
namespace Pureclarity\Core\Helper;

class Soap
{
    
    const LOG_FILE = "pureclarity_soap.log";
    protected $logger;
    protected $coreHelper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Pureclarity\Core\Helper\Data $coreHelper
    ) {
        $this->logger = $logger;
        $this->coreHelper = $coreHelper;
    }
    public function request($url, $useSSL, $payload = null)
    {
        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL, $url);
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT_MS, 5000);
        curl_setopt($soap_do, CURLOPT_TIMEOUT_MS, 10000);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($soap_do, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, 0);

        if ($payload != null){
            curl_setopt($soap_do, CURLOPT_POST, true);
            curl_setopt($soap_do, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($soap_do, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($payload)));
        }
        else {
            curl_setopt($soap_do, CURLOPT_POST, false);
        }

        curl_setopt($soap_do, CURLOPT_FAILONERROR, true);
        curl_setopt($soap_do, CURLOPT_VERBOSE, true);

        if (!$result = curl_exec($soap_do)) {
            $this->logger->debug('PURECLARITY DELTA ERROR: '.curl_error($soap_do));
        }

        curl_close($soap_do);

        $this->logger->debug("------------------ PC DELTA ------------------");
        $this->logger->debug(print_r($url,true));
        if ($payload != null)
            $this->logger->debug(print_r($payload,true));
        $this->logger->debug("------------------ RESPONSE ------------------");
        $this->logger->debug(print_r($result,true));
        $this->logger->debug("------------------ END PRODUCT DELTA ------------------");

        return $result;
    }


}
