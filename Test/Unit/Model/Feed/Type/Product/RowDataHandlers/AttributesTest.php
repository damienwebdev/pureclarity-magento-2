<?php
declare(strict_types=1);

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type\Product\RowDataHandlers;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Attributes;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use ReflectionException;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

/**
 * Class AttributesTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Attributes
 * @see \Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers\Attributes
 */
class AttributesTest extends TestCase
{
    /** @var CoreConfig | MockObject */
    private $coreConfig;

    /** @var CollectionFactory | MockObject */
    private $collectionFactory;

    /** @var Attributes */
    private $attributes;

    /**
     * setup tests
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->attributes = new Attributes(
            $this->coreConfig,
            $this->collectionFactory
        );
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Attributes::class, $this->attributes);
    }

    /**
     * Test that loadAttributes returns loaded attributes as expected.
     * @throws ReflectionException
     */
    public function testLoadAttributes(): void
    {
        $collection = $this->createPartialMock(
            Collection::class,
            ['addFieldToFilter', 'getItems']
        );

        $collection->expects(self::at(0))
            ->method('addFieldToFilter')
            ->with('attribute_code', ['nin' => ['prices', 'price', 'category_ids', 'sku']]);

        $collection->expects(self::at(1))
            ->method('addFieldToFilter')
            ->with('frontend_label', ['neq' => '']);

        $atts = [];
        for ($i = 1; $i <= 2; $i++) {
            $att = $this->createPartialMock(
                Attribute::class,
                ['getAttributeCode', '__call', 'getFrontendInput']
            );

            $att->method('getAttributeCode')
                ->willReturn('attribute_' . $i);

            $att->method('__call')
                ->with('getFrontendLabel')
                ->willReturn('Attribute ' . $i);

            $att->method('getFrontendInput')
                ->willReturn('text');

            $atts[] = $att;
        }

        $collection->expects(self::at(2))
            ->method('getItems')
            ->willReturn($atts);

        $this->collectionFactory->method('create')
            ->willReturn($collection);

        $this->coreConfig->expects(self::once())
            ->method('getExcludedProductAttributes')
            ->with(1)
            ->willReturn('');

        self::assertEquals(
            [
                [
                    'code' => 'attribute_1',
                    'label' => 'Attribute 1',
                    'type' => 'text'
                ],
                [
                    'code' => 'attribute_2',
                    'label' => 'Attribute 2',
                    'type' => 'text'
                ]
            ],
            $this->attributes->loadAttributes(1)
        );
    }

    /**
     * Test that loadExcludedAttributes returns base excluded attributes when none are configured.
     */
    public function testLoadExcludedAttributesNoExclusions(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('getExcludedProductAttributes')
            ->with(1)
            ->willReturn('');

        self::assertEquals(
            ['prices', 'price', 'category_ids', 'sku'],
            $this->attributes->loadExcludedAttributes(1)
        );
    }

    /**
     * Test that loadExcludedAttributes returns all excluded attributes when configured.
     */
    public function testLoadExcludedAttributesWithExclusions(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('getExcludedProductAttributes')
            ->with(1)
            ->willReturn('some,other,attributes');

        self::assertEquals(
            ['prices', 'price', 'category_ids', 'sku', 'some', 'other', 'attributes'],
            $this->attributes->loadExcludedAttributes(1)
        );
    }
}
