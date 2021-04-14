<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Model;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Helper\Serializer;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Dashboard;
use PureClarity\Api\Info\Dashboard as ApiDashboard;
use PureClarity\Api\Info\DashboardFactory;
use Psr\Log\LoggerInterface;

/**
 * Class DashboardTest
 *
 * Tests the methods in \Pureclarity\Core\Model\Dashboard
 */
class DashboardTest extends TestCase
{
    const ACCESS_KEY = 'AccessKey1234';
    const SECRET_KEY = 'SecretKey1234';
    const REGION_ID = 1;
    const STORE_ID = 17;
    const RESPONSE = [
        'NextSteps' => ['ns1','ns1'],
        'Account' => ['ac1','ac2'],
        'Stats' => ['st1','st2']
    ];

    /** @var Dashboard $object */
    private $object;

    /** @var MockObject|DashboardFactory $dashboardFactory */
    private $dashboardFactory;

    /** @var MockObject|CoreConfig $coreConfig */
    private $coreConfig;

    /** @var MockObject|ApiDashboard $dashboard */
    private $dashboard;

    /** @var MockObject|LoggerInterface $logger*/
    private $logger;

    protected function setUp(): void
    {
        $this->coreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer->method('serialize')->willReturnCallback(function ($param) {
            return json_encode($param);
        });

        $this->serializer->method('unserialize')->willReturnCallback(function ($param) {
            return json_decode($param, true);
        });

        $this->dashboard = $this->getMockBuilder(ApiDashboard::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboardFactory = $this->getMockBuilder(DashboardFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Dashboard(
            $this->coreConfig,
            $this->serializer,
            $this->logger,
            $this->dashboardFactory
        );
    }

    /**
     * Sets up the standard config calls used by the dashbaord class create
     */
    private function setupDashboard()
    {
        $this->coreConfig->expects(self::once())
            ->method('getAccessKey')
            ->with(self::STORE_ID)
            ->willReturn(self::ACCESS_KEY);

        $this->coreConfig->expects(self::once())
            ->method('getSecretKey')
            ->with(self::STORE_ID)
            ->willReturn(self::SECRET_KEY);

        $this->coreConfig->expects(self::once())
            ->method('getRegion')
            ->with(self::STORE_ID)
            ->willReturn(self::REGION_ID);

        $this->dashboardFactory->method('create')
            ->with([
                'accessKey' => self::ACCESS_KEY,
                'secretKey' => self::SECRET_KEY,
                'region' => self::REGION_ID
            ])
            ->willReturn($this->dashboard);
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(Dashboard::class, $this->object);
    }

    /**
     * Tests that an exception on any data call is handled
     */
    public function testException()
    {
        $this->setupDashboard();

        $this->dashboard->expects(self::once())
            ->method('request')
            ->willThrowException(new \Exception('An Error'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with('PureClarity Dashboard Error: An Error');

        $nextSteps = $this->object->getNextSteps(self::STORE_ID);

        self::assertEquals(
            [],
            $nextSteps
        );
    }

    /**
     * Tests that an getNextSteps returns the correct data
     */
    public function testGetNextSteps()
    {
        $this->setupDashboard();

        $this->dashboard->expects(self::once())
            ->method('request')
            ->willReturn(['body' => json_encode(self::RESPONSE)]);

        $nextSteps = $this->object->getNextSteps(self::STORE_ID);

        self::assertEquals(
            self::RESPONSE['NextSteps'],
            $nextSteps
        );
    }

    /**
     * Tests that an getNextSteps handles no data returned
     */
    public function testGetNextStepsEmpty()
    {
        $this->setupDashboard();

        $this->dashboard->expects(self::once())
            ->method('request')
            ->willReturn(['body' => json_encode([])]);

        $nextSteps = $this->object->getNextSteps(self::STORE_ID);

        self::assertEquals(
            [],
            $nextSteps
        );
    }

    /**
     * Tests that an getStats returns the correct data
     */
    public function testGetStats()
    {
        $this->setupDashboard();

        $this->dashboard->expects(self::once())
            ->method('request')
            ->willReturn(['body' => json_encode(self::RESPONSE)]);

        $account = $this->object->getStats(self::STORE_ID);

        self::assertEquals(
            self::RESPONSE['Stats'],
            $account
        );
    }

    /**
     * Tests that an getStats handles no data returned
     */
    public function testGetStatsEmpty()
    {
        $this->setupDashboard();

        $this->dashboard->expects(self::once())
            ->method('request')
            ->willReturn(['body' => json_encode([])]);

        $nextSteps = $this->object->getStats(self::STORE_ID);

        self::assertEquals(
            [],
            $nextSteps
        );
    }

    /**
     * Tests that an getAccountStatus returns the correct data
     */
    public function testGetAccountStatus()
    {
        $this->setupDashboard();

        $this->dashboard->expects(self::once())
            ->method('request')
            ->willReturn(['body' => json_encode(self::RESPONSE)]);

        $account = $this->object->getAccountStatus(self::STORE_ID);

        self::assertEquals(
            self::RESPONSE['Account'],
            $account
        );
    }

    /**
     * Tests that an getAccountStatus handles no data returned
     */
    public function testGetAccountStatusEmpty()
    {
        $this->setupDashboard();

        $this->dashboard->expects(self::once())
            ->method('request')
            ->willReturn(['body' => json_encode([])]);

        $nextSteps = $this->object->getAccountStatus(self::STORE_ID);

        self::assertEquals(
            [],
            $nextSteps
        );
    }
}
