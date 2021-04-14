<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed\State\Error;
use Pureclarity\Core\Model\Feed\State\Progress;
use Pureclarity\Core\Model\Feed\State\Request;
use Pureclarity\Core\Model\Feed\State\RunDate;
use Pureclarity\Core\Model\Feed\State\Running;
use Pureclarity\Core\Model\Feed\Status;
use ReflectionException;

/**
 * Class StatusTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Status
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StatusTest extends TestCase
{
    /** @var Status $object */
    private $object;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|TimezoneInterface */
    private $timezone;

    /** @var MockObject|Error */
    private $error;

    /** @var MockObject|Progress */
    private $progress;

    /** @var MockObject|Request */
    private $request;

    /** @var MockObject|RunDate */
    private $runDate;

    /** @var MockObject|Running */
    private $running;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->timezone = $this->createMock(TimezoneInterface::class);
        $this->error = $this->createMock(Error::class);
        $this->progress = $this->createMock(Progress::class);
        $this->request = $this->createMock(Request::class);
        $this->runDate = $this->createMock(RunDate::class);
        $this->running = $this->createMock(Running::class);

        $this->object = new Status(
            $this->coreConfig,
            $this->timezone,
            $this->error,
            $this->progress,
            $this->request,
            $this->runDate,
            $this->running
        );
    }

    /**
     * Tests that the class matches the expected type
     */
    public function testFeedStatusInstance(): void
    {
        self::assertInstanceOf(Status::class, $this->object);
    }

    /**
     * Tests that getFeedStatus will return "Not Enabled" when expected
     */
    public function testGetFeedStatusNotEnabled(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isActive')
            ->with(1)
            ->willReturn(false);

        $status = $this->object->getFeedStatus('product', 1);

        self::assertEquals(
            [
                'enabled' => false,
                'error' => false,
                'running' => false,
                'class' => 'pc-feed-disabled',
                'label' => 'Not Enabled',
            ],
            $status
        );
    }

    /**
     * Tests that getFeedStatus will return "Not Enabled" for brand feed when expected
     */
    public function testGetFeedStatusNotEnabledBrands(): void
    {
        $this->coreConfig->expects(self::at(0))
            ->method('isActive')
            ->with(1)
            ->willReturn(true);

        $this->coreConfig->expects(self::at(1))
            ->method('isActive')
            ->with(1)
            ->willReturn(true);

        $this->coreConfig->expects(self::at(2))
            ->method('isBrandFeedEnabled')
            ->with(1)
            ->willReturn(false);

        $status = $this->object->getFeedStatus('brand', 1);

        self::assertEquals(
            [
                'enabled' => false,
                'error' => false,
                'running' => false,
                'class' => 'pc-feed-disabled',
                'label' => 'Not Enabled',
            ],
            $status
        );
    }

    /**
     * Tests getFeedStatus returns "Not Sent" when expected
     */
    public function testGetFeedStatusNotSent(): void
    {
        $status = $this->object->getFeedStatus('product', 1);

        self::assertEquals(
            [
                'enabled' => true,
                'error' => false,
                'running' => false,
                'class' => 'pc-feed-not-sent',
                'label' => 'Not Sent',
            ],
            $status
        );
    }

    /**
     * Tests getFeedStatus returns "Error" when expected
     */
    public function testGetFeedStatusError(): void
    {
        $this->error->expects(self::once())
            ->method('getFeedError')
            ->with(1, 'product')
            ->willReturn('An Error');

        $status = $this->object->getFeedStatus('product', 1);

        self::assertEquals(
            [
                'enabled' => true,
                'error' => true,
                'running' => false,
                'class' => 'pc-feed-error',
                'label' => 'Error, please see logs for more information',
            ],
            $status
        );
    }

    /**
     * Tests getFeedStatus returns "Waiting" when expected
     */
    public function testGetFeedStatusRequested(): void
    {
        $this->request->expects(self::once())
            ->method('getStoreRequestedFeeds')
            ->with(1)
            ->willReturn(['product']);

        $status = $this->object->getFeedStatus('product', 1);

        self::assertEquals(
            [
                'enabled' => true,
                'error' => false,
                'running' => true,
                'class' => 'pc-feed-waiting',
                'label' => 'Waiting for feed run to start',
            ],
            $status
        );
    }

    /**
     * Tests getFeedStatus returns "Waiting" when expected
     */
    public function testGetFeedStatusWaiting(): void
    {
        $this->running->expects(self::once())
            ->method('getRunningFeeds')
            ->with(1)
            ->willReturn(['product']);

        $status = $this->object->getFeedStatus('product', 1);

        self::assertEquals(
            [
                'enabled' => true,
                'error' => false,
                'running' => true,
                'class' => 'pc-feed-waiting',
                'label' => 'Waiting for other feeds to finish',
            ],
            $status
        );
    }

    /**
     * Tests getFeedStatus returns "Waiting" no progress returned
     */
    public function testGetFeedStatusStillWaiting(): void
    {
        $this->running->expects(self::once())
            ->method('getRunningFeeds')
            ->with(1)
            ->willReturn(['product']);

        $this->progress->expects(self::once())
            ->method('getProgress')
            ->with(1, 'product')
            ->willReturn('');

        $status = $this->object->getFeedStatus('product', 1);

        self::assertEquals(
            [
                'enabled' => true,
                'error' => false,
                'running' => true,
                'class' => 'pc-feed-waiting',
                'label' => 'Waiting for other feeds to finish',
            ],
            $status
        );
    }

    /**
     * Tests getFeedStatus returns "In Progress" when expected
     */
    public function testGetFeedStatusInProgress(): void
    {
        $this->running->expects(self::once())
            ->method('getRunningFeeds')
            ->with(1)
            ->willReturn(['product']);

        $this->progress->expects(self::once())
            ->method('getProgress')
            ->with(1, 'product')
            ->willReturn('25');

        $status = $this->object->getFeedStatus('product', 1);

        self::assertEquals(
            [
                'enabled' => true,
                'error' => false,
                'running' => true,
                'class' => 'pc-feed-in-progress',
                'label' => 'In progress: 25%',
            ],
            $status
        );
    }

    /**
     * Tests getFeedStatus returns "Last Sent X" when expected
     */
    public function testGetFeedStatusComplete(): void
    {
        $this->runDate->expects(self::once())
            ->method('getLastRunDate')
            ->with(1, 'product')
            ->willReturn('2019-10-15 15:45:00');

        $this->timezone->method('formatDateTime')
            ->with('2019-10-15 15:45:00')
            ->willReturn('15/10/2019 15:45');

        $status = $this->object->getFeedStatus('product', 1);

        self::assertEquals(
            [
                'enabled' => true,
                'error' => false,
                'running' => false,
                'class' => 'pc-feed-complete',
                'label' => 'Last sent 15/10/2019 15:45',
            ],
            $status
        );
    }

    /**
     * Tests that areAllFeedsDisabled returns false when feeds are enabled
     */
    public function testGetAreFeedsDisabledFalse(): void
    {
        $this->coreConfig->method('isActive')
            ->with(1)
            ->willReturn(true);

        self::assertEquals(
            false,
            $this->object->areFeedsDisabled(['product'], 1)
        );
    }

    /**
     * Tests that areAllFeedsDisabled returns false when brand feed is disabled
     */
    public function testGetAreFeedsDisabledBrandFalse(): void
    {

        $this->coreConfig->method('isActive')
            ->with(1)
            ->willReturn(true);

        $this->coreConfig->method('isBrandFeedEnabled')
            ->with(1)
            ->willReturn(false);

        self::assertEquals(
            false,
            $this->object->areFeedsDisabled(['brand', 'product'], 1)
        );
    }

    /**
     * Tests that areAllFeedsDisabled returns true when feeds are disabled
     */
    public function testGetAreFeedsDisabledTrue(): void
    {
        $this->coreConfig->method('isActive')
            ->with(1)
            ->willReturn(false);

        self::assertEquals(
            true,
            $this->object->areFeedsDisabled(['product'], 1)
        );
    }

    /**
     * Tests that areFeedsInProgress returns true when feeds are in progress
     */
    public function testAreFeedsInProgressTrue(): void
    {
        $this->running->expects(self::once())
            ->method('getRunningFeeds')
            ->with(1)
            ->willReturn(['product']);

        $this->progress->expects(self::once())
            ->method('getProgress')
            ->with(1, 'product')
            ->willReturn('25');

        self::assertEquals(
            true,
            $this->object->areFeedsInProgress(['product'], 1)
        );
    }

    /**
     * Tests that areFeedsInProgress returns false when feeds are not in progress
     */
    public function testAreFeedsInProgressFalse(): void
    {
        self::assertEquals(
            false,
            $this->object->areFeedsInProgress(['product'], 1)
        );
    }
}
