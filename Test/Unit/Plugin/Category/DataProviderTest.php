<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Plugin\Category;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Phrase;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Plugin\Category\DataProvider;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;
use Magento\Catalog\Model\Category;
use Magento\Framework\Filesystem\Directory\ReadInterface;

/**
 * Class DataProviderTest
 *
 * Tests the methods in \Pureclarity\Core\Plugin\Category\DataProviderTest
 */
class DataProviderTest extends TestCase
{
    /** @var DataProvider $object */
    private $object;

    /** @var MockObject|Data $coreHelper */
    private $coreHelper;

    /** @var MockObject|StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var MockObject|Filesystem $filesystem */
    private $filesystem;

    /** @var MockObject|LoggerInterface $logger */
    private $logger;

    protected function setUp(): void
    {
        $this->coreHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new DataProvider(
            $this->coreHelper,
            $this->storeManager,
            $this->filesystem,
            $this->logger
        );
    }

    /**
     * Tests object gets instantiated correctly
     */
    public function testStateInstance(): void
    {
        self::assertInstanceOf(DataProvider::class, $this->object);
    }

    /**
     * Tests that when an exception is thrown, the error is logged
     */
    public function testAfterGetDataCategoryException(): void
    {
        $subject = $this->getMockBuilder(CategoryDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject->expects(self::once())
            ->method('getCurrentCategory')
            ->willThrowException(new NoSuchEntityException(new Phrase('A Category Error')));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity category form data error: A Category Error');

        self::assertEquals([], $this->object->afterGetData($subject, []));
    }

    /**
     * Tests that when no category is found, no data is added
     */
    public function testAfterGetDataNoCategory(): void
    {
        $subject = $this->getMockBuilder(CategoryDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject->expects(self::once())
            ->method('getCurrentCategory')
            ->willReturn(false);

        self::assertEquals([], $this->object->afterGetData($subject, []));
    }

    /**
     * Tests that when the category has no pureclarity override image, no data is added
     */
    public function testAfterGetDataNoImage()
    {
        $subject = $this->getMockBuilder(CategoryDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $category->expects(self::once())
            ->method('getData')
            ->with('pureclarity_category_image')
            ->willReturn('');

        $subject->expects(self::once())
            ->method('getCurrentCategory')
            ->willReturn($category);

        self::assertEquals([], $this->object->afterGetData($subject, []));
    }

    /**
     * Tests that when an image is selected on the category, the data is correctly added
     */
    public function testAfterGetDataWithImage(): void
    {
        $subject = $this->getMockBuilder(CategoryDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $category->method('getId')
            ->willReturn(17);

        $category->expects(self::once())
            ->method('getData')
            ->with('pureclarity_category_image')
            ->willReturn([['name' => 'abc123.jpg']]);

        $subject->expects(self::once())
            ->method('getCurrentCategory')
            ->willReturn($category);

        /** @var Store | MockObject $store1 */
        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager->method('getStore')
            ->willReturn($store);

        $this->coreHelper->expects(self::once())
            ->method('getAdminImageUrl')
            ->with($store, 'abc123.jpg', 'pureclarity_category_image')
            ->willReturn('https://abc123.jpg');

        $this->coreHelper->expects(self::once())
            ->method('getAdminImagePath')
            ->with($store, 'abc123.jpg', 'pureclarity_category_image')
            ->willReturn('/path/abc123.jpg');

        /** @var Store | MockObject $store1 */
        $read = $this->getMockBuilder(ReadInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $read->method('stat')
            ->with('/path/abc123.jpg')
            ->willReturn(['size' => 123456]);

        $this->filesystem->method('getDirectoryRead')
            ->willReturn($read);

        self::assertEquals(
            [
                17 =>
                [
                    'pureclarity_category_image' => [
                        [
                            'name' => 'abc123.jpg',
                            'url' => 'https://abc123.jpg',
                            'size' => 123456
                        ]
                    ]
                ]
            ],
            $this->object->afterGetData($subject, [])
        );
    }
}
