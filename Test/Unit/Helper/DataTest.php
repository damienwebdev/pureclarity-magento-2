<?php

namespace PureClarity\Test\Unit\Helper;

use Pureclarity\Core\Helper\Data;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Helper\Context;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Sales\Model\OrderFactory;
use \Magento\Catalog\Model\ProductFactory;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use \Magento\Framework\Filesystem\Io\FileFactory;
use \Psr\Log\LoggerInterface;
use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Store\Model\ScopeInterface;

/**
 * Class DataTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    protected $data;
    protected $scopeConfigMock;
    protected $contextMock;
    protected $storeManagerMock;
    protected $checkoutSessionMock;
    protected $orderFactoryMock;
    protected $productFactoryMock;
    protected $collectionFactoryMock;
    protected $fileFactoryMock;
    protected $loggerMock;
    protected $directoryListMock;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->contextMock = $this->createMock(Context::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        /**
         * Different construct needed for Factory classes, others generates error e.g.
         * Cannot stub or mock class or interface "Magento\Sales\Model\OrderFactory" which does not exist
         */
        $this->orderFactoryMock = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->fileFactoryMock = $this->getMockBuilder(FileFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        /**
         * Need to ensure the context returns a valid ScopeConfig so that Data->getValue() runs
         * (otherwise fails as ScopeConfig becomes null following Data constructor call to parent
         * constructor)
         */
        $this->contextMock
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);
    }


    /**
     * @dataProvider providerTestGetAccessKeyReturnsKeyWhenStoreIdIsValid
     */
    public function testGetAccessKeyReturnsKeyWhenStoreIdIsValid()
    {

        $map = array(
            array('pureclarity/credentials/access_key', ScopeInterface::SCOPE_STORE, 1, 'accesskey')
          );  

        $this->scopeConfigMock
            ->method('getValue')
            ->will($this->returnValueMap($map));

        $accessKey = $this->getData()->getAccessKey(1);
        $this->assertEquals($accessKey, 'accesskey');

    }

     /**
     * @dataProvider providerTestGetAccessKeyReturnsKeyWhenStoreIdIsValid
     */
    public function testGetAccessKeyReturnsKeyWhenStoreIdIsNotKnown()
    {

        $map = array(
            array('pureclarity/credentials/access_key', ScopeInterface::SCOPE_STORE, 1, 'accesskey')
          );  

        $this->scopeConfigMock
            ->method('getValue')
            ->will($this->returnValueMap($map));

        $accessKey = $this->getData()->getAccessKey(0);
        $this->assertEquals($accessKey, NULL);

    }

     /**
     * Test that if module is disabled, it ignores the search_active setting
     */
    public function testisSearchActiveIfPureClarityEnabled()
    {
        $map = array(
            array('pureclarity/credentials/access_key', ScopeInterface::SCOPE_STORE, 1, 'accesskey'),
            array('pureclarity/environment/active', ScopeInterface::SCOPE_STORE, 1, true),
            array('pureclarity/general_config/search_active', ScopeInterface::SCOPE_STORE, 1, true),
          );  

        $this->scopeConfigMock
            ->method('getValue')
            ->will($this->returnValueMap($map));

        $accessKey = $this->getData()->isSearchActive(1);
        $this->assertEquals($accessKey, true);

    }

     /**
     * @dataProvider providerTestGetAccessKeyReturnsKeyWhenStoreIdIsValid
     */
    public function testisSearchActiveIfPureClarityNotEnabled()
    {

        $map = array(
            array('pureclarity/credentials/access_key', ScopeInterface::SCOPE_STORE, 1, 'accesskey'),
            array('pureclarity/environment/active', ScopeInterface::SCOPE_STORE, 1, false),
            array('pureclarity/general_config/search_active', ScopeInterface::SCOPE_STORE, 1, true),
          );  

        $this->scopeConfigMock
            ->method('getValue')
            ->will($this->returnValueMap($map));

        $accessKey = $this->getData()->isSearchActive(1);
        $this->assertEquals($accessKey, false);

    }

    /**
    * Test that if module is disabled, it ignores the search_active setting
    */
    public function testisProdListingActiveIfPureClarityEnabled()
    {
        $map = array(
            array('pureclarity/credentials/access_key', ScopeInterface::SCOPE_STORE, 1, 'accesskey'),
            array('pureclarity/environment/active', ScopeInterface::SCOPE_STORE, 1, true),
            array('pureclarity/general_config/prodlisting_active', ScopeInterface::SCOPE_STORE, 1, true),
            );  

        $this->scopeConfigMock
            ->method('getValue')
            ->will($this->returnValueMap($map));

        $accessKey = $this->getData()->isProdListingActive(1);
        $this->assertEquals($accessKey, true);

    }

    /**
    * @dataProvider providerTestGetAccessKeyReturnsKeyWhenStoreIdIsValid
    */
    public function testisProdListingActiveIfPureClarityNotEnabled()
    {

        $map = array(
            array('pureclarity/credentials/access_key', ScopeInterface::SCOPE_STORE, 1, 'accesskey'),
            array('pureclarity/environment/active', ScopeInterface::SCOPE_STORE, 1, false),
            array('pureclarity/general_config/prodlisting_active', ScopeInterface::SCOPE_STORE, 1, true),
            );  

        $this->scopeConfigMock
            ->method('getValue')
            ->will($this->returnValueMap($map));

        $accessKey = $this->getData()->isProdListingActive(1);
        $this->assertEquals($accessKey, false);

    }

    /**
     * @return array
     */
    public function providerTestGetAccessKeyReturnsKeyWhenStoreIdIsValid()
    {
        return [
            [1],
        ];
    }

    private function getData(){
        if(!is_object($this->data)){
            $this->data = new Data(
                $this->contextMock,
                $this->scopeConfigMock,
                $this->storeManagerMock,
                $this->checkoutSessionMock,
                $this->orderFactoryMock,
                $this->productFactoryMock,
                $this->collectionFactoryMock,
                $this->fileFactoryMock,
                $this->loggerMock,
                $this->directoryListMock
            );
        }
        return $this->data;
    }
}