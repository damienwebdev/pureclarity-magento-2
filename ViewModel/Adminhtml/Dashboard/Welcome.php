<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\ViewModel\Adminhtml\Dashboard;

use Pureclarity\Core\Api\StateRepositoryInterface;

/**
 * Class Welcome
 *
 * Dashboard Welcome ViewModel for determining what welcome banner to display
 */
class Welcome
{
    /** @var bool $showWelcome */
    private $showWelcome;

    /** @var bool $showManualWelcome */
    private $showManualWelcome;

    /** @var bool $showGettingStarted */
    private $showGettingStarted;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /**
     * @param StateRepositoryInterface $stateRepository
     */
    public function __construct(
        StateRepositoryInterface $stateRepository
    ) {
        $this->stateRepository = $stateRepository;
    }

    /**
     * Returns whether to show the welcome banner.
     *
     * @return bool
     */
    public function showWelcomeBanner()
    {
        if ($this->showWelcome === null) {
            $state = $this->stateRepository->getByNameAndStore('show_welcome_banner', 0);
            $this->showWelcome = ($state->getId() !== null && $state->getValue() !== '0');
        }
        return $this->showWelcome;
    }

    /**
     * Returns whether to show the manual configuration welcome banner.
     *
     * @return bool
     */
    public function showManualWelcomeBanner()
    {
        if ($this->showManualWelcome === null) {
            $state = $this->stateRepository->getByNameAndStore('show_manual_welcome_banner', 0);
            $this->showManualWelcome = ($state->getId() !== null && $state->getValue() !== '0');
        }
        return $this->showManualWelcome;
    }

    /**
     * Returns whether to show the post-welcome banner.
     *
     * @return bool
     */
    public function showGettingStartedBanner()
    {
        if ($this->showGettingStarted === null) {
            $state = $this->stateRepository->getByNameAndStore('show_getting_started_banner', 0);
            $showTime = $state->getValue();
            $this->showGettingStarted = false === $this->showWelcomeBanner()
                && false === $this->showManualWelcomeBanner()
                && (false === empty($showTime))
                && time() < $showTime;
        }
        return $this->showGettingStarted;
    }
}
