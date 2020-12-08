<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Block\Adminhtml\Dashboard\Stats;
use Pureclarity\Core\Model\Dashboard;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class StatsTest
 *
 * Tests the methods in \Pureclarity\Core\Block\Adminhtml\Dashboard\Stats
 */
class StatsTest extends TestCase
{
    /** @var Stats $object */
    private $object;

    /** @var MockObject|Context $context */
    private $context;

    /** @var MockObject|Dashboard $dashboard */
    private $dashboard;

    /** @var MockObject|Stores $stores */
    private $stores;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboard = $this->getMockBuilder(Dashboard::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stores = $this->getMockBuilder(Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Stats(
            $this->context,
            $this->dashboard,
            $this->stores
        );
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(Stats::class, $this->object);
    }

    /**
     * Tests that getPureclarityStoresViewModel returns the right class
     */
    public function testGetPureclarityStoresViewModel()
    {
        self::assertInstanceOf(Stores::class, $this->object->getPureclarityStoresViewModel());
    }

    /**
     * Tests that getNextSteps returns the data returned by the dashboard class
     */
    public function testGetNextSteps()
    {
        $this->dashboard->method('getStats')
            ->with(17)
            ->willReturn(['data']);
        self::assertEquals(['data'], $this->object->getStats(17));
    }

    /**
     * Tests that getStatTitle returns the correct title for the "today" type
     */
    public function testGetStatTitleToday()
    {
        self::assertEquals(
            'Today',
            $this->object->getStatTitle('today')
        );
    }

    /**
     * Tests that getStatTitle returns the correct title for the "last30days" type
     */
    public function testGetStatTitle30Days()
    {
        self::assertEquals(
            'Last 30 days',
            $this->object->getStatTitle('last30days')
        );
    }

    /**
     * Tests that hasRecTotalStats returns the correct flag when there is a recommender total to show
     */
    public function testHasRecTotalStatsTrue()
    {
        self::assertEquals(
            true,
            $this->object->hasRecTotalStats([
                'RecommenderProductTotal' => 17,
                'RecommenderProductTotalDisplay' => '£17.00',
                'OrderCount' => 1,
                'SalesTotalDisplay' => '£100.00'
            ])
        );
    }

    /**
     * Tests that hasRecTotalStats returns the correct flag when there isn't a recommender total to show due to no data
     */
    public function testHasRecTotalStatsFalseNoData()
    {
        self::assertEquals(
            false,
            $this->object->hasRecTotalStats([])
        );
    }

    /**
     * Tests that hasRecTotalStats returns the correct flag when there isn't a recommender total to show due to no total
     */
    public function testHasRecTotalStatsFalseNoTotal()
    {
        self::assertEquals(
            false,
            $this->object->hasRecTotalStats([
                'RecommenderProductTotal' => 0,
                'RecommenderProductTotalDisplay' => '£00.00',
                'OrderCount' => 0,
                'SalesTotalDisplay' => '£00.00'
            ])
        );
    }

    /**
     * Tests that getStatKeysToShow returns the correct stats to show
     */
    public function testGetStatKeysToShow()
    {
        self::assertEquals(
            [
                'Impressions'                    => 'Impressions',
                'Sessions'                       => 'Sessions',
                'ConversionRate'                 => 'Conversion Rate',
                'SalesTotalDisplay'              => 'Sales Total',
                'OrderCount'                     => 'Orders',
                'RecommenderProductTotalDisplay' => 'Recommender Product Total',
            ],
            $this->object->getStatKeysToShow()
        );
    }

    /**
     * Tests that getStatDisplay formats a random field correctly (i.e. no formatting)
     */
    public function testGetStatDisplayUnformatted()
    {
        self::assertEquals(
            17,
            $this->object->getStatDisplay('SomeKey', '17')
        );
    }

    /**
     * Tests that getStatDisplay formats the ConversionRate field correctly
     */
    public function testGetStatDisplayFormatted()
    {
        self::assertEquals(
            '17%',
            $this->object->getStatDisplay('ConversionRate', '17')
        );
    }
}
