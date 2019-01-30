<?php

namespace PureClarity\Test\Unit\Helper;

use Pureclarity\Core\Helper\Data;

use \Magento\Catalog\Model\ProductFactory;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\Filesystem\Io\FileFactory;
use \Magento\Sales\Model\OrderFactory;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Psr\Log\LoggerInterface;

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
        $this->scopeConfigMock = $this->createMock( ScopeConfigInterface::class );
        $this->contextMock = $this->createMock( Context::class );
        $this->storeManagerMock = $this->createMock( StoreManagerInterface::class );
        $this->checkoutSessionMock = $this->createMock( CheckoutSession::class );
        $this->loggerMock = $this->createMock( LoggerInterface::class );
        $this->directoryListMock = $this->createMock( DirectoryList::class );

        /**
         * Different construct needed for Factory classes, others generates error e.g.
         * Cannot stub or mock class or interface "Magento\Sales\Model\OrderFactory" which does not exist
         */
        $this->orderFactoryMock = $this->getMockBuilder( OrderFactory::class )
            ->disableOriginalConstructor()
            ->setMethods( [ 'create' ] )
            ->getMock();
        $this->productFactoryMock = $this->getMockBuilder( ProductFactory::class )
            ->disableOriginalConstructor()
            ->setMethods( [ 'create' ] )
            ->getMock();
        $this->productFactoryMock = $this->getMockBuilder( ProductFactory::class )
            ->disableOriginalConstructor()
            ->setMethods( [ 'create' ] )
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder( CollectionFactory::class )
            ->disableOriginalConstructor()
            ->setMethods( [ 'create' ] )
            ->getMock();
        $this->fileFactoryMock = $this->getMockBuilder( FileFactory::class )
            ->disableOriginalConstructor()
            ->setMethods( [ 'create' ] )
            ->getMock();

        /**
         * Need to ensure the context returns a valid ScopeConfig so that Data->getValue() runs
         * (otherwise fails as ScopeConfig becomes null following Data constructor call to parent
         * constructor)
         */
        $this->contextMock
            ->method( 'getScopeConfig' )
            ->willReturn( $this->scopeConfigMock );
    }


    /**
     * Tests Pureclarity\Core\Helper\Data->getAccessKey() returns the correct key
     * for a valid store id.
     * @dataProvider validStoreIdDataProvider
     */
    public function testGetAccessKeyReturnsKeyWhenStoreIdIsValid( $storeId )
    {
        $map = [
            [
                'pureclarity/credentials/access_key', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                'accesskey'
            ]
        ];
        $this->setScopeConfigGetValueReturnValue( $map );

        $accessKey = $this->getData()->getAccessKey( $storeId );
        $this->assertEquals( $accessKey, 'accesskey' );
    }

    /**
     * Tests Pureclarity\Core\Helper\Data->getAccessKey() correctly returns null if the 
     * store id is invalid.
     * @dataProvider invalidStoreIdDataProvider
     */
    public function testGetAccessKeyReturnsNullWhenStoreIdIsInvalid( $storeId, $invalidStoreId )
    {
        $map = [
            [
                'pureclarity/credentials/access_key', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                'accesskey'
            ]
        ];
        $this->setScopeConfigGetValueReturnValue( $map );

        $accessKey = $this->getData()->getAccessKey( $invalidStoreId );
        $this->assertEquals( $accessKey, NULL );
    }

     /**
     * Tests that if PureClarity is enabled, Pureclarity\Core\Helper\Data->isSearchActive()
     * returns true if search is turned on in the configuration settings.
     * @dataProvider validStoreIdDataProvider
     */
    public function testisSearchActiveIfPureClarityEnabled( $storeId )
    {
        $map = [
            [
                'pureclarity/credentials/access_key', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                'accesskey'
            ],
            [
                'pureclarity/environment/active', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                true
            ],
            [
                'pureclarity/general_config/search_active', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                true
            ],
        ];
        $this->setScopeConfigGetValueReturnValue( $map );

        $isSearchActive = $this->getData()->isSearchActive( $storeId );
        $this->assertEquals( $isSearchActive, true );
    }

    /**
     * Tests that if PureClarity is disabled, Pureclarity\Core\Helper\Data->isSearchActive()
     * returns false, even if search is registered as being turned on within the configuration
     * settings.
     * @dataProvider validStoreIdDataProvider
     */
    public function testisSearchActiveIfPureClarityDisabled( $storeId )
    {
        $map = [
            [
                'pureclarity/credentials/access_key', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                'accesskey'
            ],
            [
                'pureclarity/environment/active', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                false
            ],
            [
                'pureclarity/general_config/search_active', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                true
            ],
        ];
        $this->setScopeConfigGetValueReturnValue( $map );

        $isSearchActive = $this->getData()->isSearchActive( $storeId );
        $this->assertEquals( $isSearchActive, false );
    }

    /**
     * Tests that if PureClarity is enabled, Pureclarity\Core\Helper\Data->isProdListingActive()
     * returns true if product listing is registered as being turned on within the configuration settings.
     * @dataProvider validStoreIdDataProvider
     */
    public function testisProdListingActiveIfPureClarityEnabled( $storeId )
    {
        $map = [
            [
                'pureclarity/credentials/access_key', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                'accesskey'
            ],
            [
                'pureclarity/environment/active', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                true
            ],
            [
                'pureclarity/general_config/prodlisting_active', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                true
            ],
        ];
        $this->setScopeConfigGetValueReturnValue( $map );

        $isProdListingActive = $this->getData()->isProdListingActive( $storeId );
        $this->assertEquals( $isProdListingActive, true );
    }

    /**
     * Tests that if PureClarity is disabled, Pureclarity\Core\Helper\Data->isProdListingActive()
     * returns false even if product listing is registered as being turned on within the configuration settings.
     * @dataProvider validStoreIdDataProvider
     */
    public function testisProdListingActiveIfPureClarityDisabled( $storeId )
    {
        $map = [
            [
                'pureclarity/credentials/access_key', 
                ScopeInterface::SCOPE_STORE, 
                $storeId, 
                'accesskey'
            ],
            [
                'pureclarity/environment/active',
                ScopeInterface::SCOPE_STORE,
                $storeId,
                false
            ],
            [
                'pureclarity/general_config/prodlisting_active',
                ScopeInterface::SCOPE_STORE,
                $storeId,
                true
            ],
        ];
        $this->setScopeConfigGetValueReturnValue( $map );

        $isProdListingActive = $this->getData()->isProdListingActive( $storeId );
        $this->assertEquals( $isProdListingActive, false );
    }

    /**
     * @return array valid store ids
     */
    public function validStoreIdDataProvider()
    {
        return [
            [ 
                1,
            ],
        ];
    }

    /**
     * @return array invalid store ids
     */
    public function invalidStoreIdDataProvider()
    {
        return [
            [ 
                1,
                0,
            ],
        ];
    }

    private function getData()
    {
        if( ! is_object( $this->data ) ) {
            $this->data = new Data(
                $this->contextMock,
                $this->storeManagerMock,
                $this->checkoutSessionMock,
                $this->orderFactoryMock,
                $this->productFactoryMock,
                $this->collectionFactoryMock,
                $this->fileFactoryMock,
                $this->directoryListMock,
                $this->loggerMock
            );
        }
        return $this->data;
    }

    /**
     * Sets what the return value will be of the getValue() method, of the scopeConfig
     * object returned by the \Magento\Framework\App\Helper\Context->getScopeConfig() 
     * function.
     */
    private function setScopeConfigGetValueReturnValue( $map )
    {
        $this->scopeConfigMock
            ->method( 'getValue' )
            ->will( $this->returnValueMap( $map ) );
    }
}