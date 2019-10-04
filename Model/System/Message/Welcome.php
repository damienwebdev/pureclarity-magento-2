<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;

/**
 * Class Welcome
 *
 * Displays welcome notification in admin if module is not configured
 */
class Welcome implements MessageInterface
{
    /** @var string */
    const MESSAGE_IDENTITY = 'pureclarity_system_message';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var StateRepositoryInterface */
    private $stateRepository;

    /** @var UrlInterface */
    private $url;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StateRepositoryInterface $stateRepository
     * @param UrlInterface $url
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StateRepositoryInterface $stateRepository,
        UrlInterface $url
    ) {
        $this->scopeConfig     = $scopeConfig;
        $this->stateRepository = $stateRepository;
        $this->url             = $url;
    }

    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    }

    /**
     * Check whether the system message should be shown - only shown if no access key configured
     *
     * @return bool
     */
    public function isDisplayed()
    {
        $shouldDisplay = false;
        $accessKey = $this->scopeConfig->getValue('pureclarity/credentials/access_key');

        if (!$accessKey) {
            $state = $this->stateRepository->getByNameAndStore('is_configured', 0);
            if ($state->getId() === null) {
                $shouldDisplay = true;
            }
        }

        return $shouldDisplay;
    }

    /**
     * Retrieve system message text
     *
     * @return Phrase
     */
    public function getText()
    {
        $text = 'Welcome to <strong>PureClarity</strong> Personalization!'
            . ' Please <a href="' . $this->url->getUrl('pureclarity/dashboard/index') . '">click here to go to the '
            . 'plugin dashboard</a> to begin setting up <strong>PureClarity</strong>.'
            . ' You can also access this from the Content submenu in the main menu';

        return __($text, ['strong', 'a', 'br']);
    }

    /**
     * Retrieve system message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }
}
