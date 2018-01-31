<?php

namespace Pureclarity\Core\Block\BMZs;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;

class ProductRecommender extends Template implements BlockInterface
{
    private $productListHtml = "";

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Psr\Log\LoggerInterface $logger,
        \Pureclarity\Core\Helper\Service $service,
        array $data = []
    ) {
        $this->coreHelper = $coreHelper;
        $this->logger = $logger;
        $this->service = $service;
        parent::__construct(
            $context,
            $data
        );
    }

    public function getTitle(){
        return $this->getBmzData()['title'];
    }

    public function getProducts(){
        return $this->getBmzData()['items'];
    }

    public function _beforeToHtml(){
        $skus = [];
        $clickEvents = [];
        foreach($this->getProducts() as $item){
            $skus[] = $item['Sku'];
            $clickEvents[$item['Sku']] = $item['ClickEvt'];
        }
        $condition = implode(', ', $skus);
        $this->productListHtml = $this->getLayout()
            ->createBlock("Magento\CatalogWidget\Block\Product\ProductsList", "pc_bmz_serverside_prodrec_" . $this->getBmzId())
            ->setData('products_per_page', 20)
            ->setData('products_count', 20)
            ->setData('cache_lifetime', 5)
            ->setData('pureclarity_click_events', $clickEvents)
            ->setData('conditions_encoded', "^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,`attribute`:`sku`,`operator`:`()`,`value`:`$condition`^]^]")
            ->setTemplate("Pureclarity_Core::grid.phtml")
            ->toHtml();
    }

    public function getProductList(){
        return $this->productListHtml;
    }
}