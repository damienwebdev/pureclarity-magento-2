<?php

namespace Pureclarity\Core\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Pcadminbutton extends Field
{
    protected $_template = 'Pureclarity_Core::system/config/pcadminbutton.phtml';
    protected $coreHelper;

    public function __construct(
        Context $context,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreHelper = $coreHelper;
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    public function getAdminUrl()
    {
        return $this->coreHelper->getAdminUrl();
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData(
                [
                'id' => 'PC_Admin',
                'label' => "Go to admin",
                'onclick' => 'javascript:pureclarity_magento_go_to_admin(); return false;'
                ]
            );

        return $button->toHtml();
    }
}
