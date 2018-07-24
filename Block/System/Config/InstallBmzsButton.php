<?php
namespace Pureclarity\Core\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class InstallBmzsButton extends Field
{
    protected $_template = 'Pureclarity_Core::system/config/install_bmzs_button.phtml';
    protected $urlBuilder;
    protected $storeManagerInterface;
    protected $logger;
    protected $labelFactory;
    
    public function __construct(
        Context $context,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\View\Design\Theme\LabelFactory $labelFactory,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->urlBuilder = $urlBuilder;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->logger = $logger;
        $this->labelFactory = $labelFactory;
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    public function getStores($withDefault = true, $codeKey = false)
    {
        $stores=[];
        $stores[] = [
            "id" => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
            "name" => "All Store Views"
        ];
        foreach ($this->storeManagerInterface->getStores() as $store) {
            $stores[] = [
                "id" => $store->getId(),
                "name" => $store->getWebsite()->getName() . ' - ' . $store->getName()
            ];
        }
        return $stores;
    }

    public function getThemes()
    {
        $label = $this->labelFactory->create();
        return $label->getLabelsCollection();
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

    public function getInstallUrl()
    {
        return $this->urlBuilder->getUrl('adminhtml/bmz/install');
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
                'id' => 'pc-bmzpopupbutton',
                'label' => 'Install BMZs'
            ]);

        return $button->toHtml();
    }
}
