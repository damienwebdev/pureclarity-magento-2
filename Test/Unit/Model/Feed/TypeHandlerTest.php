<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model\Feed;

use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Model\Feed\TypeHandler;
use PHPUnit\Framework\MockObject\MockObject;
use Pureclarity\Core\Model\Feed\Type\UserFactory;
use Pureclarity\Core\Model\Feed\Type\User;
use PureClarity\Api\Feed\Feed;

/**
 * Class TypeHandlerTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Feed\TypeHandler
 */
class TypeHandlerTest extends TestCase
{
    /** @var TypeHandler */
    private $object;

    /** @var MockObject|UserFactory */
    private $userFeed;

    protected function setUp(): void
    {
        $this->userFeed = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new TypeHandler(
            $this->userFeed
        );
    }

    /**
     * Tests the class gets setup correctly
     */
    public function testInstance(): void
    {
        self::assertInstanceOf(TypeHandler::class, $this->object);
    }

    /**
     * Tests that an error is thrown if an invalid feed type is passed
     */
    public function testGetFeedHandlerInvalid(): void
    {
        $this->userFeed->expects(self::never())
            ->method('create');

        try {
            $this->object->getFeedHandler('fish');
        } catch (\Exception $e) {
            self::assertEquals('PureClarity feed type not recognised: fish', $e->getMessage());
        }
    }

    /**
     * Tests that a user feed class is returned correctly
     */
    public function testGetFeedHandlerUser(): void
    {
        $userFeed = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userFeed->expects(self::once())
            ->method('create')
            ->willReturn($userFeed);

        $handler = $this->object->getFeedHandler(Feed::FEED_TYPE_USER);
        self::assertInstanceOf(User::class, $handler);
    }
}
