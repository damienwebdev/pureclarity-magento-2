<?php

namespace Pureclarity\Core\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;

class Bmz extends Template implements BlockInterface
{

    public $debug;
    public $isServerSide = false;

    protected $bmzId;
    protected $content;
    protected $classes;
    protected $bmzData = "";
    protected $coreHelper;
    protected $logger;
    protected $registry;
    protected $cmsBlockFactory;
    protected $storeManager;
    protected $_template = "bmz.phtml";
    protected $service;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Cms\Model\BlockFactory $cmsBlockFactory,
        \Pureclarity\Core\Helper\Service $service,
        array $data = []
    ) {
        $this->coreHelper = $coreHelper;
        $this->logger = $context->getLogger();
        $this->registry = $registry;
        $this->cmsBlockFactory = $cmsBlockFactory;
        $this->storeManager = $context->getStoreManager();
        $this->service = $service;
        parent::__construct(
            $context,
            $data
        );
    }
    
    public function setData($key, $value = null)
    {
        if ($key == 'bmz_id' && $this->coreHelper->isServerSide()) {
            $this->service->addZone($value);
        }
        parent::setData($key, $value);
    }

    protected function _toHtml()
    {
        if ($this->coreHelper->isMerchActive()) {
            return parent::_toHtml();
        }
        return '';
    }

    public function addBmzData($field, $value)
    {
        $this->bmzData = $this->bmzData . $field . ':' . $value . ';';
    }

    public function _beforeToHtml()
    {
        if (!$this->coreHelper->isMerchActive()) {
            return;
        }

        if ($this->coreHelper->isServerSide()) {
            $this->service->dispatch();
        }

        // Get some parameters
        $this->debug = $this->coreHelper->isBMZDebugActive();
        $this->bmzId = $this->escapeHtml($this->getData('bmz_id'));
        $this->isServerSide = $this->coreHelper->isServerSide();

        if ($this->bmzId == null or $this->bmzId == "") {
            $this->logger->error("PureClarity: BMZ block instantiated without a BMZ Id.");
        } else {
            $this->addBmzData('bmz', $this->bmzId);

            // Set product data
            $product = $this->registry->registry("product");
            if ($product != null) {
                $this->addBmzData('sku', $product->getId());
            }

            // Set category data
            $categoryObject = $this->registry->registry('current_category');
            if (is_object($categoryObject)) {
                $this->addBmzData('categoryid', $categoryObject->getId());
            }
        }

        // Generate debug text if needed
        $debugContent = '';
        if ($this->debug) {
            $debugContent = "<p>PureClarity BMZ: $this->bmzId</p>";
        }

        // Get the fallback content
        $fallbackContent = '';
        $fallbackCmsBlock = $this->getData('bmz_fallback_cms_block');

        $storeId = $this->_storeManager->getStore()->getId();
        $fallbackBlock = $this->cmsBlockFactory->create()->setStoreId($storeId)->load($fallbackCmsBlock);
        
        if ($fallbackBlock && $fallbackBlock->getIsActive()) {
            $fallbackContent = $fallbackBlock->getContent();
            if ($this->debug) {
                $debugContent .= "<p>Fallback block: $fallbackCmsBlock.</p>";
            }
        }

        // Get Server side BMZ Content if we need to
        $serverSideContent = '';
        if ($this->isServerSide) {
            $serverSideContent = $this->getServerSideBmzBlock();
            if ($serverSideContent != "" && $fallbackContent) {
                $fallbackContent = "";
            }
        }

        // The actual content is the debug content followed by the fallback content.
        // In most cases; content will be an empty string
        $content = $debugContent . $fallbackContent . $serverSideContent;

        // Get a list of the custom classes for this BMZs div tag
        $customClasses = $this->getData('pc_bmz_classes');
        if ($customClasses) {
            $allClasses = explode(",", $customClasses);
        } else {
            $allClasses = [];
        }

        // Check for desktop-specific or mobile-specific BMZs
        $displayMode = $this->getData('pc_bmz_display_mode');

        // Add more classes to the class list where they are needed to identify desktop-specific or mobile-specific BMZs
        if ($displayMode == "mobile") {
            $allClasses[] = "pureclarity_magento_mobile";
        } elseif ($displayMode == "desktop") {
            $allClasses[] = "pureclarity_magento_desktop";
        }

        $applyBuffer = $this->getData('pc_bmz_buffer');
        if ($applyBuffer == 1 || $applyBuffer == "true") {
            $allClasses[] = "pc_bmz_buffer";
        }

        // Content is now final
        $this->content = $content;

        // Classes are now final
        $allClasses[] = "pc_bmz";
        $allClassesStr = implode(" ", $allClasses);
        $allClassesStr = $this->escapeHtml($allClassesStr);
        $this->classes = $allClassesStr;
    }

    public function getDebugMode()
    {
        return $this->debug;
    }

    public function getBmzId()
    {
        return $this->bmzId;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function getBmzData()
    {
        if ($this->isServerSide) {
            return "data-pureclarity-server=\"$this->bmzId\"";
        }
        return "data-pureclarity=\"$this->bmzData\"";
    }

    public function getCacheLifetime()
    {
        return null;
    }

    public function getServerSideBmzBlock()
    {

        $result = $this->service->getResult();

        if ($result &&
            array_key_exists('zones', $result) &&
            array_key_exists($this->bmzId, $result['zones']) &&
            array_key_exists('type', $result['zones'][$this->bmzId])) {
            $resultData = $result['zones'][$this->bmzId];

            switch ($result['zones'][$this->bmzId]['type']) {
                case "recommender-product":
                    return $this->getLayout()
                            ->createBlock("Pureclarity\Core\Block\BMZs\ProductRecommender", "pc_bmz_serverside_" . $this->bmzId)
                            ->setData('bmz_id', $this->bmzId)
                            ->setData('bmz_data', $resultData)
                            ->setTemplate($this->coreHelper->getProductRecommenderTemplate())
                            ->toHtml();
                case "recommender-category":
                case "recommender-brand":
                case "staticimage":
                case "carousel":
                case "html":
                    return $resultData['html'];
            }
        }
        return "";
    }
}
