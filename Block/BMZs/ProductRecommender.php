<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\BMZs;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use Magento\Widget\Helper\Conditions;
use Magento\CatalogWidget\Model\Rule\Condition\Combine;
use Magento\CatalogWidget\Model\Rule\Condition\Product;
use Magento\CatalogWidget\Block\Product\ProductsList;

class ProductRecommender extends Template implements BlockInterface
{
    /** @var string */
    private $productListHtml = '';
    
    /** @var \Magento\Widget\Helper\Conditions */
    private $conditionsHelper;

    public function __construct(
        Context $context,
        Conditions $conditionsHelper,
        array $data = []
    ) {
        $this->conditionsHelper = $conditionsHelper;
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
        
        $conditions = [
                1 => [
                    'type' => Combine::class,
                    'aggregator' => 'all',
                    'value' => '1',
                    'new_child' => '',
                ],
                '1--1' => [
                    'type' => Product::class,
                    'attribute' => 'sku',
                    'operator' => '()',
                    'value' => $condition,
                ]
            ];
            
        $conditionsEncoded = $this->conditionsHelper->encode($conditions);

        $this->productListHtml = $this->getLayout()
            ->createBlock(ProductsList::class, 'pc_bmz_serverside_prodrec_' . $this->getBmzId())
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
