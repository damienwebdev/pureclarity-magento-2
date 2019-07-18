<?php

namespace Pureclarity\Core\CustomerData;

class Cart implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    protected $cart;
    protected $logger;
    protected $productCollection;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
    ) {
        $this->cart = $cart;
        $this->logger = $logger;
        $this->productCollection = $productCollection;
    }
    
    public function getSectionData()
    {
        $items = [];
        $visibleItems = $this->cart->getQuote()->getAllVisibleItems();
        $allItems = $this->cart->getQuote()->getAllItems();
        foreach ($visibleItems as $item) {
            $items[$item->getItemId()] = [
                'id' => $item->getProductId(),
                'qty' => $item->getQty(),
                'unitprice' => $item->getPrice(),
                'refid' => $item->getItemId(),
                'children' => []
            ];
        }
        foreach ($allItems as $item) {
            if ($item->getParentItemId() && isset($items[$item->getParentItemId()])) {
                $items[$item->getParentItemId()]['children'][] = ["sku" => $item->getSku(), "qty" => $item->getQty()];
            }
        }

        $data = [
            "items" => []
        ];
        foreach ($items as $item) {
            $data['items'][] = $item;
        }

        return $data;
    }
}
