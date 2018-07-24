<?php

namespace Pureclarity\Core\Block\Search;

class ListProduct extends \Magento\Framework\View\Element\Template
{
    
    protected $coreHelper;
    protected $logger;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        $this->coreHelper = $coreHelper;
        $this->logger = $logger;
        parent::__construct(
            $context,
            $data
        );
    }

    public function _beforeToHtml()
    {
        if ($this->coreHelper->isSearchActive()) {
            $this->setTemplate('Pureclarity_Core::autocomplete.phtml');
        }
            
        return parent::_beforeToHtml();
    }
}
