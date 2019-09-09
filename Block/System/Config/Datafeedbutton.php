<?php
namespace Pureclarity\Core\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Datafeedbutton extends Field
{
    protected $_template = 'Pureclarity_Core::system/config/data_feed_button.phtml';
    protected $urlBuilder;
    protected $storeManagerInterface;
    protected $logger;
    
    public function __construct(
        Context $context,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->urlBuilder = $urlBuilder;
        $this->storeManagerInterface = $context->getStoreManager();
        $this->logger = $context->getLogger();
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    public function getStores($withDefault = false, $codeKey = false)
    {
        $stores=[];
        foreach ($this->storeManagerInterface->getStores() as $store) {
            $stores[] = [
                "id" => $store->getId(),
                "website" => $store->getWebsite()->getName(),
                "name" => $store->getName()
            ];
        }
        return $stores;
    }

    /**
     * Return element html
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getFeedExecutionUrl()
    {
        return $this->urlBuilder->getUrl('adminhtml/datafeed/runfeed');
    }

    public function showOrderImportOption()
    {
        return true;
    }

    public function getFeedProgressUrl()
    {
        return $this->urlBuilder->getUrl('adminhtml/datafeed/progress');
    }

    public function getDeltasUrl()
    {
        return $this->urlBuilder->getUrl('adminhtml/datafeed/deltas');
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData([
                'id' => 'pc-feedpopupbutton',
                'label' => 'Data Feed'
            ]);

        return $button->toHtml();
    }
}
