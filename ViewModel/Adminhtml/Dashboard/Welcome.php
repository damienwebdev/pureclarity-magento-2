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
    /** @var string $welcomeType */
    private $welcomeType;

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
     * @param integer $storeId
     * @return bool
     */
    public function showWelcomeBanner($storeId)
    {
        if ($this->showWelcome === null) {
            $this->showWelcome = ($this->getWelcomeType($storeId) === 'auto');
        }
        return $this->showWelcome;
    }

    /**
     * Returns whether to show the manual configuration welcome banner.
     *
     * @param integer $storeId
     * @return bool
     */
    public function showManualWelcomeBanner($storeId)
    {
        if ($this->showManualWelcome === null) {
            $this->showManualWelcome = ($this->getWelcomeType($storeId) === 'manual');
        }
        return $this->showManualWelcome;
    }

    /**
     * Returns whether to show the post-welcome banner.
     *
     * @param integer $storeId
     * @return bool
     */
    public function showGettingStartedBanner($storeId)
    {
        if ($this->showGettingStarted === null) {
            $state = $this->stateRepository->getByNameAndStore('show_getting_started_banner', $storeId);
            $showTime = $state->getValue();
            $this->showGettingStarted = false === $this->showWelcomeBanner($storeId)
                && false === $this->showManualWelcomeBanner($storeId)
                && (false === empty($showTime))
                && time() < $showTime;
        }
        return $this->showGettingStarted;
    }

    /**
     * Gets the welcome banner type from the state table
     *
     * @param integer $storeId
     * @return string
     */
    public function getWelcomeType($storeId)
    {
        if ($this->welcomeType === null) {
            $state = $this->stateRepository->getByNameAndStore('show_welcome_banner', $storeId);
            $this->welcomeType = ($state->getId() !== null) ? $state->getValue() : '';
        }
        return $this->welcomeType;
    }
}
