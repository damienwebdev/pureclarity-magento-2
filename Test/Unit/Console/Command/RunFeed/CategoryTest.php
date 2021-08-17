<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Console\Command\RunFeed;

use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Console\Command\RunFeed\Category;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PureClarity\Api\Feed\Feed;
use Pureclarity\Core\Model\Feed\Runner;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;

/**
 * Class CategoryTest
 *
 * Tests methods in \Pureclarity\Core\Console\Command\RunFeed\Category
 * @see \Pureclarity\Core\Console\Command\RunFeed\Category
 */
class CategoryTest extends TestCase
{
    /** @var Category $object */
    private $object;

    /** @var MockObject|Runner $feedRunner */
    private $feedRunner;

    /** @var MockObject|State $state */
    private $state;

    /** @var MockObject|StoreManagerInterface $storeManager */
    private $storeManager;

    protected function setUp() : void
    {
        $this->feedRunner = $this->createMock(Runner::class);
        $this->state = $this->createMock(State::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->object = new Category(
            $this->feedRunner,
            $this->state,
            $this->storeManager
        );
    }

    /**
     * Sets up the get stores call with the provided store IDs
     * @param int[] $storeIds
     */
    private function setupGetStores(array $storeIds): void
    {
        $stores = [];
        foreach ($storeIds as $storeId) {
            $store = $this->createMock(StoreInterface::class);

            $store->method('getId')
                ->willReturn($storeId);

            $stores[$storeId] = $store;
        }

        $this->storeManager->expects(self::at(0))
            ->method('getStores')
            ->willReturn($stores);
    }

    /**
     * Tests class is set up correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Category::class, $this->object);
    }

    /**
     * Tests class is set up correctly
     */
    public function testCommand(): void
    {
        self::assertInstanceOf(Command::class, $this->object);
    }

    /**
     * Tests feed gets run on a single store
     */
    public function testExecuteSingleStore(): void
    {
        $this->setupGetStores([1]);
        $this->feedRunner->expects(self::once())->method('selectedFeeds')->with(1, [Feed::FEED_TYPE_CATEGORY]);
        $this->runExecute(false);
    }

    /**
     * Tests state error gets shown
     */
    public function testExecuteStateError(): void
    {
        $this->setupGetStores([1]);
        $this->feedRunner->expects(self::once())->method('selectedFeeds')->with(1, [Feed::FEED_TYPE_CATEGORY]);
        $this->runExecute(true);
    }

    /**
     * Tests feed gets run on multi-store
     */
    public function testExecuteMultiStore(): void
    {
        $this->setupGetStores([1, 17, 42]);
        $this->feedRunner->expects(self::at(0))->method('selectedFeeds')->with(1, [Feed::FEED_TYPE_CATEGORY]);
        $this->feedRunner->expects(self::at(1))->method('selectedFeeds')->with(17, [Feed::FEED_TYPE_CATEGORY]);
        $this->feedRunner->expects(self::at(2))->method('selectedFeeds')->with(42, [Feed::FEED_TYPE_CATEGORY]);
        $this->runExecute();
    }

    /**
     * sets up and runs execute function
     * @param bool $error
     */
    private function runExecute(bool $error = false): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        if ($error === false) {
            $this->state->expects(self::once())->method('setAreaCode');
        } else {
            $this->state->expects(self::once())->method('setAreaCode')->willThrowException(new \Exception('An error'));
            $output->expects(self::at(0))->method('writeln')->with('An error');
        }
        $this->state->expects(self::once())->method('setAreaCode')->with('adminhtml');
        $this->object->execute($input, $output);
    }
}
