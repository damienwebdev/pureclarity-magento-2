<?php

namespace PureClarity\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Helper\Data;
use \Magento\Catalog\Model\ProductFactory;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\Filesystem\Io\FileFactory;
use \Magento\Sales\Model\OrderFactory;
use \Magento\Store\Model\StoreManagerInterface;
use \Psr\Log\LoggerInterface;

/**
 * Class DataTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class DataTest extends TestCase
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
            ->setMethods([ 'create' ])
            ->getMock();
        $this->productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'create' ])
            ->getMock();
        $this->productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'create' ])
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'create' ])
            ->getMock();
        $this->fileFactoryMock = $this->getMockBuilder(FileFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'create' ])
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
        if (! is_object($this->data)) {
            $this->data = new Data(
                $this->scopeConfigMock,
                $this->storeManagerMock,
                $this->checkoutSessionMock,
                $this->fileFactoryMock,
                $this->directoryListMock
            );
        }
        return $this->data;
    }

    /**
     * Sets what the return value will be of the getValue() method, of the scopeConfig
     * object returned by the \Magento\Framework\App\Helper\Context->getScopeConfig()
     * function.
     */
    private function setScopeConfigGetValueReturnValue($map)
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->will($this->returnValueMap($map));
    }
}
