<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\ResourceModel;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\ResourceModel\ProductFeed;
use Magento\Framework\Model\ResourceModel\Db\Context;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ProductFeedTest
 *
 * Tests the methods in \Pureclarity\Core\Model\ResourceModel\ProductFeed
 */
class ProductFeedTest extends TestCase
{
    /** @var ProductFeed $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);

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
