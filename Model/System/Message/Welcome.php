<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pureclarity\Core\Model\CoreConfig;

/**
 * Class Welcome
 *
 * Displays welcome notification in admin if module is not configured
 */
class Welcome implements MessageInterface
{
    /** @var string */
    const MESSAGE_IDENTITY = 'pureclarity_system_message';

    /** @var UrlInterface $url */
    private $url;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /**
     * @param UrlInterface $url
     * @param StoreManagerInterface $storeManager
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        UrlInterface $url,
        StoreManagerInterface $storeManager,
        CoreConfig $coreConfig
    ) {
        $this->url             = $url;
        $this->storeManager    = $storeManager;
        $this->coreConfig      = $coreConfig;
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
        $shouldDisplay = true;

        // Check all stores for a configured store, and if one is found, set to not display
        foreach ($this->storeManager->getStores() as $storeView) {
            $accessKey = $this->coreConfig->getAccessKey($storeView->getId());
            $secretKey = $this->coreConfig->getSecretKey($storeView->getId());
            if ($accessKey && $secretKey) {
                $shouldDisplay = false;
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
