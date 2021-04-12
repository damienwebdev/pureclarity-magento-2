<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Feed\Requester;
use Pureclarity\Core\Model\Feed\Runner;
use Pureclarity\Core\Model\Feed\State\Error;
use Pureclarity\Core\Model\Feed\State\Progress;
use Pureclarity\Core\Model\Feed\State\Request;
use ReflectionException;

/**
 * Class RequesterTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\RequesterTest
 */
class RequesterTest extends TestCase
{
    /** @var Requester $object */
    private $object;

    /** @var MockObject|CoreConfig */
    private $coreConfig;

    /** @var MockObject|Request */
    private $request;

    /** @var MockObject|Progress */
    private $progress;

    /** @var MockObject|Error */
    private $error;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->coreConfig = $this->createMock(CoreConfig::class);
        $this->request = $this->createMock(Request::class);
        $this->progress = $this->createMock(Progress::class);
        $this->error = $this->createMock(Error::class);

        $this->object = new Requester(
            $this->coreConfig,
            $this->request,
            $this->progress,
            $this->error
        );
    }

    /**
     * Tests that the class matches the expected type
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(Requester::class, $this->object);
    }

    /**
     * Tests that requestFeeds will not try to request feeds if disabled
     */
    public function testRequestFeedsDisabled(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isActive')
            ->with(1)
            ->willReturn(false);

        $this->request->expects(self::never())
            ->method('requestFeeds');
        $this->error->expects(self::never())
            ->method('saveFeedError');
        $this->progress->expects(self::never())
            ->method('updateProgress');

        $this->object->requestFeeds(1, Runner::VALID_FEED_TYPES);
    }

    /**
     * Tests that getFeedStatus will request feeds if enabled
     */
    public function testRequestFeedsEnabled(): void
    {
        $this->coreConfig->expects(self::once())
            ->method('isActive')
            ->with(1)
            ->willReturn(true);

        $this->request->expects(self::once())
            ->method('requestFeeds')
            ->with(1, Runner::VALID_FEED_TYPES);
        
        $this->error->expects(self::exactly(5))
            ->method('saveFeedError');
        
        $this->progress->expects(self::exactly(5))
            ->method('updateProgress');

        $this->object->requestFeeds(1, Runner::VALID_FEED_TYPES);
    }
}
