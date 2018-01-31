<?php 

use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
 
class MyClass
{
    private $remoteAddress;
 
    public function __construct(RemoteAddress $remoteAddress)
    {
        $this->remoteAddress = $remoteAddress;
    }
 
    public function doSomething()
    {
        $ipAddressOfTheClient = $this->remoteAddress->getRemoteAddress();
    }
}