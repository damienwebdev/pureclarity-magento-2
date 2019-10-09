<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\ResourceModel;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\Data\StateInterface;
use Pureclarity\Core\Model\ResourceModel\ProductFeed;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class ProductFeedTest
 *
 * @category   Tests
 * @package    PureClarity
 */
class ProductFeedTest extends TestCase
{
    /** @var ProductFeed $object */
    private $object;

    /** @var Context $context */
    private $context;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new ProductFeed($this->context);
    }

    public function testInstance()
    {
        $this->assertInstanceOf(ProductFeed::class, $this->object);
    }

    public function testIdFieldName()
    {
        $this->assertEquals('id', $this->object->getIdFieldName());
    }
}
