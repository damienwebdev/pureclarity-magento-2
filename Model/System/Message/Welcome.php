<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;

class Welcome implements MessageInterface
{
    /** @var string */
    const MESSAGE_IDENTITY = 'pureclarity_system_message';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
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
        return $this->scopeConfig->getValue('pureclarity/credentials/access_key') ? false : true;
    }

    /**
     * Retrieve system message text
     *
     * @return Phrase
     */
    public function getText()
    {
        $text = 'Welcome to <strong>PureClarity</strong> Personalization! <br /><br />'
        . '<strong>Setting up your account:</strong><br /><br />'
        . 'Please get in touch with <a href="mailto:support@pureclarity.com">support@pureclarity.com</a> to request an '
        . 'account. Your Success Manager will get you set up within one working day. <br /><br />'
        . 'Finalize your implementation by putting in your access keys, found in PureClarity admin under My Account > '
        . 'My Profile. <br /><br />To learn more about the implementation '
        . '<a href="https://support.pureclarity.com/hc/en-us/sections/360000118834-Magento-2-x" target="_blank">'
        . 'click here</a>.<br /><br />'
        . 'Check out our pricing information <a href="https://www.pureclarity.com/pricing/" target="_blank">here</a>.';

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
