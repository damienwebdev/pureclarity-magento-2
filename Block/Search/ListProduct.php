<?php

namespace Pureclarity\Core\Block\Search;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    
    protected $coreHelper;
    protected $logger;
    protected $pureClarityService;
    protected $personalizedProductListHtml = "";

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Pureclarity\Core\Helper\Service $pureClarityService,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        array $data = []
    ) {
        $this->coreHelper = $coreHelper;
        $this->pureClarityService = $pureClarityService;
        $this->logger = $context->getLogger();
        
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }

    public function _beforeToHtml()
    {

        $collection = $this->_getProductCollection();

        if ($this->coreHelper->isSearchActive() && $this->coreHelper->isServerSide()) {
            $searchResult = $this->pureClarityService->getSearchResult();
            if ($searchResult && array_key_exists('refId', $searchResult) && array_key_exists('clickEventName', $searchResult)) {
                $this->setPureClarityClickData([
                    "event" => $searchResult['clickEventName'],
                    "refId" => $searchResult['refId']
                ]);
            }

            $this->createPersonalizedProductList();

            $this->setTemplate($this->coreHelper->getProductListTemplate());
        }
            
        return parent::_beforeToHtml();
    }

    public function getPureClarityClickEvent($id)
    {
        $eventData = $this->getPureClarityClickData();
        if ($eventData) {
            $event = $eventData['event'];
            $refId = $eventData['refId'];
            return "_pc('$event', { id: '$id', refid: '$refId' });";
        }
        return "";
    }

    public function createPersonalizedProductList()
    {
        
        $searchResult = $this->pureClarityService->getSearchResult();
        
        if ($searchResult && array_key_exists('personalizedProducts', $searchResult)) {
            $products = $searchResult['personalizedProducts'];
            
            if ($products && sizeof($products) > 0) {
                $skus = [];
                $clickEvents = [];
                foreach ($products as $item) {
                    $skus[] = $item['Sku'];
                    $clickEvents[$item['Id']] = $this->getPureClarityClickEvent($item['Id']);
                }
                
                $condition = implode(', ', $skus);
                $this->personalizedProductListHtml = $this->getLayout()
                    ->createBlock("Magento\CatalogWidget\Block\Product\ProductsList", "pc_bmz_serverside_prodrec_" . $this->getBmzId())
                    ->setData('products_per_page', 20)
                    ->setData('products_count', 20)
                    ->setData('cache_lifetime', 5)
                    ->setData('title', $searchResult['personalizedProductsTitle'])
                    ->setData('pureclarity_click_events', $clickEvents)
                    ->setData('conditions_encoded', "^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,`attribute`:`sku`,`operator`:`()`,`value`:`$condition`^]^]")
                    ->setTemplate("Pureclarity_Core::grid.phtml")
                    ->toHtml();
            }
        }
    }

    public function getPersonalizedProductListHtml()
    {
        return $this->personalizedProductListHtml;
    }
}
