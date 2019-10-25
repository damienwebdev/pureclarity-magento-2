<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Config\Source;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Config\Source\Categories;

/**
 * Class CategoriesTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Config\Source\Categories
 */
class CategoriesTest extends TestCase
{
    /** @var Categories $object */
    private $object;

    /** @var MockObject|StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var MockObject|Website $website */
    private $website;

    /** @var MockObject|CategoryRepository $categoryRepository */
    private $categoryRepository;

    /** @var MockObject|Group $group */
    private $group;

    protected function setUp()
    {
        $this->categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->group = $this->getMockBuilder(Group::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->website = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Categories(
            $this->categoryRepository,
            $this->storeManager
        );
    }

    private function initData()
    {
        /** @var Store | MockObject $store1 */
        $store1 = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store1->expects($this->any())
               ->method('getRootCategoryId')
               ->willReturn(1);

        /** @var Store | MockObject $store2 */
        $store2 = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store2->expects($this->any())
            ->method('getRootCategoryId')
            ->willReturn(2);

        $this->group->expects($this->any())
            ->method('getStores')
            ->willReturn([$store1, $store2]);

        $this->website->expects($this->any())
            ->method('getGroups')
            ->willReturn([$this->group]);

        $this->storeManager->expects($this->any())
            ->method('getWebsites')
            ->willReturn([$this->website]);

        $category1 = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $category1->expects($this->any())
            ->method('getName')
            ->willReturn('Category 1');

        $category1->expects($this->any())
            ->method('getId')
            ->willReturn('1');

        $category2 = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $category2->expects($this->any())
            ->method('getName')
            ->willReturn('Category 2');

        $category2->expects($this->any())
            ->method('getId')
            ->willReturn('2');

        $category3 = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $category3->expects($this->any())
            ->method('getName')
            ->willReturn('Category 3');

        $category3->expects($this->any())
            ->method('getId')
            ->willReturn('3');

        $category1->expects($this->any())->method('getChildrenCategories')->willReturn([]);
        $category2->expects($this->any())->method('getChildrenCategories')->willReturn([$category3]);
        $category3->expects($this->any())->method('getChildrenCategories')->willReturn([]);

        $this->categoryRepository->expects($this->at(0))
            ->method('get')
            ->with(1)
            ->willReturn($category1);

        $this->categoryRepository->expects($this->at(1))
            ->method('get')
            ->with(2)
            ->willReturn($category2);

        $this->categoryRepository->expects($this->at(2))
            ->method('get')
            ->with(3)
            ->willReturn($category3);
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Categories::class, $this->object);
    }

    public function testInterface()
    {
        $this->assertInstanceOf(OptionSourceInterface::class, $this->object);
    }

    public function testToOptionArray()
    {
        $this->initData();
        $categories = $this->object->toOptionArray();

        $expected = [
            [
                'label' => "  ",
                'value' => "-1"
            ],
            [
                'value' => "1",
                'label' => "Category 1"
            ],
            [
                'value' => "2",
                'label' => "Category 2"
            ],
            [
                'value' => "3",
                'label' => "Category 2 -> Category 3"
            ]
        ];

        $this->assertEquals($expected, $categories);
    }
}
