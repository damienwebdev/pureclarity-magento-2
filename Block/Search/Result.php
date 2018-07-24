<?php

namespace Pureclarity\Core\Block\Search;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\CatalogSearch\Helper\Data;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Model\QueryFactory;

class Result extends \Magento\CatalogSearch\Block\Result
{
    
    protected $coreHelper;
    protected $logger;
    protected $pureClarityService;

    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Psr\Log\LoggerInterface $logger,
        \Pureclarity\Core\Helper\Service $pureClarityService,
        array $data = []
    ) {
        $this->coreHelper = $coreHelper;
        $this->logger = $logger;
        $this->pureClarityService = $pureClarityService;
        
        parent::__construct(
            $context,
            $layerResolver,
            $catalogSearchData,
            $queryFactory,
            $data
        );
    }

    public function _beforeToHtml()
    {
        

        if ($this->coreHelper->isSearchActive() && $this->coreHelper->isServerSide()) {
            $this->setTemplate($this->coreHelper->getResultTemplate());
        }
            
        return parent::_beforeToHtml();
    }

    public function showNoResultRecommenders()
    {
        
        $searchResult = $this->pureClarityService->getSearchResult();

        if ($searchResult &&
            $searchResult['zeroResults'] &&
            array_key_exists('recommenders', $searchResult) &&
            sizeof($searchResult['recommenders']) > 0) {
            return true;
        }
        return false;
    }

    public function getNoResultsRecommendersHtml()
    {
        
        $searchResult = $this->pureClarityService->getSearchResult();

        $html = "";

        if ($this->showNoResultRecommenders()) {
            $count = 1;
            foreach ($searchResult['recommenders'] as $recommender) {
                $skus = [];
                $clickEvents = [];
                foreach ($recommender['items'] as $item) {
                    $skus[] = $item['Sku'];
                    $clickEvents[$item['Id']] = $this->getPureClarityClickEvent($item['Id']);
                }
                
                $condition = implode(', ', $skus);
                $html = $html . $this->getLayout()
                    ->createBlock("Magento\CatalogWidget\Block\Product\ProductsList", "pc_search_serverside_rec_" . $count)
                    ->setData('products_per_page', 6)
                    ->setData('products_count', 6)
                    ->setData('cache_lifetime', 5)
                    ->setData('title', $recommender['title'])
                    ->setData('pureclarity_click_events', $clickEvents)
                    ->setData('conditions_encoded', "^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,`attribute`:`sku`,`operator`:`()`,`value`:`$condition`^]^]")
                    ->setTemplate("Pureclarity_Core::grid.phtml")
                    ->toHtml();

                $count++;
            }

            return $html;
        }
    }
}
