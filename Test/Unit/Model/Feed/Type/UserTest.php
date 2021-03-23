<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed\Type;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\Type\User;
use PHPUnit\Framework\MockObject\MockObject;
use PureClarity\Api\Feed\Type\UserFactory;
use Pureclarity\Core\Api\UserFeedDataManagementInterface;
use Pureclarity\Core\Api\UserFeedRowDataManagementInterface;
use Pureclarity\Core\Api\FeedManagementInterface;
use PureClarity\Api\Feed\Feed;
use Pureclarity\Core\Api\FeedDataManagementInterface;
use Pureclarity\Core\Api\FeedRowDataManagementInterface;

/**
 * Class UserTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\Type\User
 */
class UserTest extends TestCase
{
    /** @var User */
    private $object;

    /** @var MockObject|UserFactory */
    private $userFeedFactory;

    /** @var MockObject|UserFeedDataManagementInterface */
    private $feedDataHandler;

    /** @var MockObject|UserFeedRowDataManagementInterface */
    private $rowDataHandler;

    protected function setUp(): void
    {
        $this->userFeedFactory = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedDataHandler = $this->getMockBuilder(UserFeedDataManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rowDataHandler = $this->getMockBuilder(UserFeedRowDataManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new User(
            $this->userFeedFactory,
            $this->feedDataHandler,
            $this->rowDataHandler
        );
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(User::class, $this->object);
    }

    /**
     * Tests the class implements the right interface
     */
    public function testImplements(): void
    {
        self::assertInstanceOf(FeedManagementInterface::class, $this->object);
    }

    /**
     * Tests that getFeedBuilder passes the right info to the feed builder factory class
     */
    public function testGetFeedBuilder(): void
    {
        $feed = $this->getMockBuilder(Feed::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userFeedFactory->expects(self::once())
            ->method('create')
            ->with([
                'accessKey' => 'A',
                'secretKey' => 'B',
                'region' => 1
            ])
            ->willReturn($feed);

        $feedBuilder = $this->object->getFeedBuilder('A', 'B', 1);
        self::assertInstanceOf(Feed::class, $feedBuilder);
    }

    /**
     * Tests getFeedDataHandler returns the right class
     */
    public function testGetFeedDataHandler(): void
    {
        self::assertInstanceOf(FeedDataManagementInterface::class, $this->object->getFeedDataHandler());
    }

    /**
     * Tests getRowDataHandler returns the right class
     */
    public function testGetRowDataHandler(): void
    {
        self::assertInstanceOf(FeedRowDataManagementInterface::class, $this->object->getRowDataHandler());
    }
}
