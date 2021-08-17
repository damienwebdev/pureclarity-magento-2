<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Test\Unit\ViewModel\Adminhtml\Dashboard;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Welcome;
use Pureclarity\Core\Model\State as StateModel;

/**
 * Class WelcomeTest
 *
 * Tests the methods in \Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Welcome
 */
class WelcomeTest extends TestCase
{
    /** @var Welcome $object */
    private $object;

    /** @var MockObject|StateRepositoryInterface $stateRepository */
    private $stateRepository;

    protected function setUp(): void
    {
        $this->stateRepository = $this->createMock(StateRepositoryInterface::class);

        $this->object = new Welcome(
            $this->stateRepository
        );
    }

    /**
     * Generates a State Mock
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $storeId
     * @return MockObject
     */
    private function getStateMock($id = null, $name = null, $value = null, $storeId = null)
    {
        $state = $this->createMock(StateModel::class);

        $state->method('getId')
            ->willReturn($id);

        $state->method('getStoreId')
            ->willReturn($storeId);

        $state->method('getName')
            ->willReturn($name);

        $state->method('getValue')
            ->willReturn($value);

        return $state;
    }

    /**
     * Tests class gets instantiated correctly
     */
    public function testInstance()
    {
        self::assertInstanceOf(Welcome::class, $this->object);
    }

    /**
     * Tests that showWelcomeBanner returns false when no state value is present
     */
    public function testShowWelcomeBannerFalse()
    {
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('show_welcome_banner', 17)
            ->willReturn($this->getStateMock());

        self::assertEquals(false, $this->object->showWelcomeBanner(17));
        // second run to test caching
        self::assertEquals(false, $this->object->showWelcomeBanner(17));
    }

    /**
     * Tests that showWelcomeBanner returns false when state is "manual"
     */
    public function testShowWelcomeBannerFalseManual()
    {
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('show_welcome_banner', 17)
            ->willReturn($this->getStateMock(1, 'show_welcome_banner', 'manual', 17));

        self::assertEquals(false, $this->object->showWelcomeBanner(17));
        // second run to test caching
        self::assertEquals(false, $this->object->showWelcomeBanner(17));
    }

    /**
     * Tests that showWelcomeBanner returns true when state is "auto"
     */
    public function testShowWelcomeBannerTrue()
    {
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('show_welcome_banner', 17)
            ->willReturn($this->getStateMock(1, 'show_welcome_banner', 'auto', 17));

        self::assertEquals(true, $this->object->showWelcomeBanner(17));
        // second run to test caching
        self::assertEquals(true, $this->object->showWelcomeBanner(17));
    }

    /**
     * Tests that showManualWelcomeBanner returns false when state is empty
     */
    public function testShowManualWelcomeBannerFalse()
    {
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('show_welcome_banner', 17)
            ->willReturn($this->getStateMock());

        self::assertEquals(false, $this->object->showManualWelcomeBanner(17));
    }

    /**
     * Tests that showManualWelcomeBanner returns false when state is "auto"
     */
    public function testShowManualWelcomeBannerFalseAuto()
    {
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('show_welcome_banner', 17)
            ->willReturn($this->getStateMock(1, 'show_welcome_banner', 'auto', 17));

        self::assertEquals(false, $this->object->showManualWelcomeBanner(17));
        // second run to test caching
        self::assertEquals(false, $this->object->showManualWelcomeBanner(17));
    }

    /**
     * Tests that showManualWelcomeBanner returns true when state is "manual"
     */
    public function testShowManualWelcomeBannerTrue()
    {
        $this->stateRepository->expects(self::once())
            ->method('getByNameAndStore')
            ->with('show_welcome_banner', 17)
            ->willReturn($this->getStateMock(1, 'show_welcome_banner', 'manual', 17));

        self::assertEquals(true, $this->object->showManualWelcomeBanner(17));
        // second run to test caching
        self::assertEquals(true, $this->object->showManualWelcomeBanner(17));
    }

    /**
     * Tests that showGettingStartedBanner returns false when state is empty
     */
    public function testShowGettingStartedBannerFalseEmpty()
    {
        $this->stateRepository->expects(self::at(0))
            ->method('getByNameAndStore')
            ->with('show_getting_started_banner', 17)
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects(self::at(1))
            ->method('getByNameAndStore')
            ->with('show_welcome_banner', 17)
            ->willReturn($this->getStateMock());

        self::assertEquals(false, $this->object->showGettingStartedBanner(17));
        // second run to test caching
        self::assertEquals(false, $this->object->showGettingStartedBanner(17));
    }

    /**
     * Tests that showGettingStartedBanner returns false when show_welcome_banner state is present
     */
    public function testShowGettingStartedBannerFalseShowBanner()
    {
        $this->stateRepository->expects(self::at(0))
            ->method('getByNameAndStore')
            ->with('show_getting_started_banner', 17)
            ->willReturn($this->getStateMock());

        $this->stateRepository->expects(self::at(1))
            ->method('getByNameAndStore')
            ->with('show_welcome_banner', 17)
            ->willReturn($this->getStateMock(1, 'show_welcome_banner', 'manual', 17));

        self::assertEquals(false, $this->object->showGettingStartedBanner(17));
        // second run to test caching
        self::assertEquals(false, $this->object->showGettingStartedBanner(17));
    }

    /**
     * Tests that showGettingStartedBanner returns true
     * when show_getting_started_banner state value is a time in the future
     */
    public function testShowGettingStartedBannerTrue()
    {
        $this->stateRepository->expects(self::at(0))
            ->method('getByNameAndStore')
            ->with('show_getting_started_banner', 17)
            ->willReturn($this->getStateMock(1, 'show_getting_started_banner', time() + 100, 17));

        $this->stateRepository->expects(self::at(1))
            ->method('getByNameAndStore')
            ->with('show_welcome_banner', 17)
            ->willReturn($this->getStateMock());

        self::assertEquals(true, $this->object->showGettingStartedBanner(17));
        // second run to test caching
        self::assertEquals(true, $this->object->showGettingStartedBanner(17));
    }
}
