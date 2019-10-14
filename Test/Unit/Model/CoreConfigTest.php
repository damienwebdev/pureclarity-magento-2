<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\CoreConfig;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DataTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class CoreConfigTest extends TestCase
{
    /** @var CoreConfig $object */
    private $object;

    /** @var MockObject|ScopeConfigInterface $scopeConfig */
    private $scopeConfig;

    /** @var MockObject|WriterInterface $writer */
    private $writer;

    protected function setUp()
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->writer = $this->getMockBuilder(WriterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new CoreConfig($this->scopeConfig, $this->writer);
    }

    public function testCoreConfigInstance()
    {
        $this->assertInstanceOf(CoreConfig::class, $this->object);
    }

    public function testIsActiveTrue()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $active = $this->object->isActive(1);
        $this->assertEquals(true, $active);
    }

    public function testIsActiveNoAccessKey()
    {
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn(null);
        $active = $this->object->isActive(1);
        $this->assertEquals(false, $active);
    }

    public function testIsActiveFalse()
    {
        $this->scopeConfig->expects($this->any())->method('isSetFlag')->willReturn(false);
        $active = $this->object->isActive(1);
        $this->assertEquals(false, $active);
    }

    public function testGetAccessKey()
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $accessKey = $this->object->getAccessKey(1);
        $this->assertEquals('AccessKey', $accessKey);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $accessKey = $this->object->getAccessKey(1);
        $this->assertEquals(null, $accessKey);
    }

    public function testGetSecretKey()
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_SECRET_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('SecretKey');

        $result = $this->object->getSecretKey(1);
        $this->assertEquals('SecretKey', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_SECRET_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getSecretKey(1);
        $this->assertEquals(null, $result);
    }

    public function testGetRegion()
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_REGION, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(4);

        $result = $this->object->getRegion(1);
        $this->assertEquals(4, $result);

        // Test without value, should return 1
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_REGION, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getRegion(1);
        $this->assertEquals(1, $result);
    }

    public function testIsDailyFeedActiveTrue()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_DAILY_FEED_ENABLED, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->isDailyFeedActive(1);
        $this->assertEquals(true, $result);
    }

    public function testIsDailyFeedActiveFalse()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_DAILY_FEED_ENABLED, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->isDailyFeedActive(1);
        $this->assertEquals(false, $result);
    }

    public function testIsDailyFeedActiveFalseNotActive()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->isDailyFeedActive(1);
        $this->assertEquals(false, $result);
    }

    public function testAreDeltasEnabledTrue()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_PRODUCT_INDEX, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->AreDeltasEnabled(1);
        $this->assertEquals(true, $result);
    }

    public function testAreDeltasEnabledFalse()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_PRODUCT_INDEX, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->AreDeltasEnabled(1);
        $this->assertEquals(false, $result);
    }

    public function testAreDeltasEnabledNotActive()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->AreDeltasEnabled(1);
        $this->assertEquals(false, $result);
    }

    public function testSendCustomerGroupPricingTrue()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_CUSTOMER_GROUP_PRICING, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->sendCustomerGroupPricing(1);
        $this->assertEquals(true, $result);
    }

    public function testSendCustomerGroupPricingFalse()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_CUSTOMER_GROUP_PRICING, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->sendCustomerGroupPricing(1);
        $this->assertEquals(false, $result);
    }

    public function testSendCustomerGroupPricingNotActive()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->sendCustomerGroupPricing(1);
        $this->assertEquals(false, $result);
    }

    public function testBrandFeedEnabledTrue()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_BRAND_FEED_ENABLED, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->isBrandFeedEnabled(1);
        $this->assertEquals(true, $result);
    }

    public function testBrandFeedEnabledFalse()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('AccessKey');

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        // Test with value
        $this->scopeConfig->expects($this->at(2))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_BRAND_FEED_ENABLED, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->isBrandFeedEnabled(1);
        $this->assertEquals(false, $result);
    }

    public function testBrandFeedEnabledNotActive()
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->isBrandFeedEnabled(1);
        $this->assertEquals(false, $result);
    }

    public function testGetBrandParentCategory()
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_BRAND_CATEGORY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('SecretKey');

        $result = $this->object->getBrandParentCategory(1);
        $this->assertEquals('SecretKey', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_BRAND_CATEGORY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getBrandParentCategory(1);
        $this->assertEquals(null, $result);
    }

    public function testGetProductPlaceholderUrl()
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_PRODUCT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('SecretKey');

        $result = $this->object->getProductPlaceholderUrl(1);
        $this->assertEquals('SecretKey', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_PRODUCT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getProductPlaceholderUrl(1);
        $this->assertEquals(null, $result);
    }

    public function testGetCategoryPlaceholderUrl()
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_CATEGORY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('SecretKey');

        $result = $this->object->getCategoryPlaceholderUrl(1);
        $this->assertEquals('SecretKey', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_CATEGORY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getCategoryPlaceholderUrl(1);
        $this->assertEquals(null, $result);
    }

    public function testGetSecondaryCategoryPlaceholderUrl()
    {
        // Test with value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_CATEGORY_SECONDARY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('SecretKey');

        $result = $this->object->getSecondaryCategoryPlaceholderUrl(1);
        $this->assertEquals('SecretKey', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_PLACEHOLDER_CATEGORY_SECONDARY, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getSecondaryCategoryPlaceholderUrl(1);
        $this->assertEquals(null, $result);
    }

    public function testIsZoneDebugActive()
    {
        // Test with value = true
        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ZONE_DEBUG, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->isZoneDebugActive(1);
        $this->assertEquals(true, $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_ZONE_DEBUG, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->isZoneDebugActive(1);
        $this->assertEquals(false, $result);
    }

    public function testGetNumberSwatchesPerProduct()
    {
        // Test with value = true
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_SWATCHES_PER_PRODUCT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('16');

        $result = $this->object->getNumberSwatchesPerProduct(1);
        $this->assertEquals('16', $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(CoreConfig::CONFIG_PATH_SWATCHES_PER_PRODUCT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(null);

        $result = $this->object->getNumberSwatchesPerProduct(1);
        $this->assertEquals(null, $result);
    }

    public function testShowSwatches()
    {
        // Test with value = true
        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_SWATCHES_IN_PRODUCT_LIST, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(true);

        $result = $this->object->showSwatches(1);
        $this->assertEquals(true, $result);

        // Test without value
        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(CoreConfig::CONFIG_PATH_SWATCHES_IN_PRODUCT_LIST, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn(false);

        $result = $this->object->showSwatches(1);
        $this->assertEquals(false, $result);
    }

    public function testSetAccessKeyWithDefault()
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, 'ABCDE', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

        $this->object->setAccessKey('ABCDE', 0);
    }

    public function testSetAccessKeyStore()
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_ACCESS_KEY, 'ABCDE', ScopeInterface::SCOPE_STORES, 1);

        $this->object->setAccessKey('ABCDE', 1);
    }

    public function testSetSecretKey()
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_SECRET_KEY, 'ABCDE', ScopeInterface::SCOPE_STORES, 1);

        $this->object->setSecretKey('ABCDE', 1);
    }

    public function testSetRegion()
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_REGION, 1, ScopeInterface::SCOPE_STORES, 1);

        $this->object->setRegion(1, 1);
    }

    public function testSetIsActive()
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_ACTIVE, 1, ScopeInterface::SCOPE_STORES, 1);

        $this->object->setIsActive(1, 1);
    }

    public function testSetIsDailyFeedActive()
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_DAILY_FEED_ENABLED, 1, ScopeInterface::SCOPE_STORES, 1);

        $this->object->setIsDailyFeedActive(1, 1);
    }

    public function testSetDeltasEnabled()
    {
        $this->writer->expects($this->exactly(1))
            ->method('save');

        $this->writer->expects($this->at(0))
            ->method('save')
            ->with(CoreConfig::CONFIG_PATH_PRODUCT_INDEX, 1, ScopeInterface::SCOPE_STORES, 1);

        $this->object->setDeltasEnabled(1, 1);
    }
}
