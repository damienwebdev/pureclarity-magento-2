<?php

namespace Pureclarity\Core\Block\BMZs;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;

class ProductRecommender extends Template implements BlockInterface
{
    private $productListHtml = "";
    private $productMetadata;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Pureclarity\Core\Helper\Service $service,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        $this->coreHelper = $coreHelper;
        $this->logger = $context->getLogger();
        $this->service = $service;
        $this->productMetadata = $productMetadata;
        parent::__construct(
            $context,
            $data
        );
    }

    public function getTitle()
    {
        return $this->getBmzData()['title'];
    }

    public function getProducts()
    {
        return $this->getBmzData()['items'];
    }

    public function _beforeToHtml()
    {
        $skus = [];
        $clickEvents = [];
        foreach ($this->getProducts() as $item) {
            $skus[] = $item['Sku'];
            if (isset($item['ClickEvt'])) {
                $clickEvents[$item['Id']] = $item['ClickEvt'];
            } elseif (isset($item['clickEvt'])) {
                $clickEvents[$item['Id']] = $item['clickEvt'];
            }
        }

        $condition = implode(', ', $skus);
        
        // IF >=2.2, json_encode, otherwise serialize:
        $isMagentoUsesJson = ( !defined("\\Magento\\Framework\\AppInterface::VERSION") && version_compare($this->productMetadata->getVersion(), '2.2.0', '>=') );
        $conditions = [
                1 => [
                    'type' => \Magento\CatalogWidget\Model\Rule\Condition\Combine::class,
                    'aggregator' => 'all',
                    'value' => '1',
                    'new_child' => '',
                ],
                '1--1' => [
                    'type' => \Magento\CatalogWidget\Model\Rule\Condition\Product::class,
                    'attribute' => 'sku',
                    'operator' => '()',
                    'value' => $condition,
                ]
            ];
        $conditionsEncoded = ( $isMagentoUsesJson ? json_encode($conditions) : serialize($conditions) );
        // $conditionsEncoded = "^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,`attribute`:`sku`,`operator`:`()`,`value`:`$condition`^]^]";

        // print_r($clickEvents);exit;

        $this->productListHtml = $this->getLayout()
            ->createBlock("Magento\CatalogWidget\Block\Product\ProductsList", "pc_bmz_serverside_prodrec_" . $this->getBmzId())
            ->setData('products_per_page', 20)
            ->setData('products_count', 20)
            ->setData('cache_lifetime', 5)
            ->setData('pureclarity_click_events', $clickEvents)
            ->setData('pureclarity_custom_sku_order', $skus)
            ->setConditionsEncoded($conditionsEncoded)
            ->setTemplate("Pureclarity_Core::grid.phtml")
            ->toHtml();
    }

    public function getProductList()
    {
        return $this->productListHtml;
    }
}
