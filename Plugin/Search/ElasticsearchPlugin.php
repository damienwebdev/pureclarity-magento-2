<?php
namespace Pureclarity\Core\Plugin\Search;

use Magento\Elasticsearch7\Model\Client\Elasticsearch;
use Magento\Framework\App\Request\Http;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Helper\Service;

class ElasticsearchPlugin
{
    /** @var Http */
    private $request;

    /** @var LoggerInterface */
    private $logger;

    /** @var Service */
    private $service;

    /** @var Data */
    private $coreHelper;

    /** @var mixed[] */
    private $searchResult;

    /**
     * @param Http $request
     * @param LoggerInterface $logger
     * @param Data $coreHelper
     * @param Service $service
     */
    public function __construct(
        Http $request,
        LoggerInterface $logger,
        Data $coreHelper,
        Service $service
    ) {
        $this->request    = $request;
        $this->logger     = $logger;
        $this->service    = $service;
        $this->coreHelper = $coreHelper;
    }

    /**
     * @param Elasticsearch $subject
     * @param mixed[] $query
     * @return array
     */
    public function beforeQuery($subject, $query)
    {
        try {
            if ($this->isPureClarityEnabled()) {
                $result = $this->getSearchResult();

                if ($result) {
                    $query['body']['query']['bool'] = $this->processQuery($query['body']['query']['bool']);
                    $processedResult = $this->processResult($result);

                    foreach ($processedResult['boosts'] as $boost) {
                        $query['body']['query']['bool']['should'][] = $boost;
                    }

                    $query['body']['query']['bool']['filter'] = ['ids' => [ 'values' => $processedResult['ids'] ]];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('PureClarity Search Error: ' . $e->getMessage());
        }

        return [$query];
    }

    /**
     * Removes unnecessary filters / terms / category ids being passed to elasticsearch
     *
     * @param mixed[] $query
     * @return mixed|null
     */
    public function processQuery($query)
    {
        $newQuery = [];
        foreach ($query as $type => $terms) {
            if (is_array($terms)) {
                foreach ($terms as $term) {
                    // if its the search page, remove matches, as we've already done the matching
                    // if its the category page, also remove category ids
                    if (!isset($term['match']) &&
                        !isset($term['match_phrase_prefix']) &&
                        ($this->isSearchPage() || !isset($term['term']['category_ids']))
                    ) {
                        $newQuery[$type][] = $term;
                    }
                }
            } else {
                $newQuery[$type] = $terms;
            }
        }

        return $newQuery;
    }

    /**
     * Processes the result, adding the relevant filters and boosts to make elasticsearch return the right products
     *
     * @param mixed[] $result
     * @return mixed|null
     */
    public function processResult($result)
    {
        $personalProductIds = $this->processPersonalisedProducts($result);
        $rank = count($result['products']);
        $resultIds = [];
        $boosts = [];
        foreach ($result['products'] as $product) {
            if (!isset($personalProductIds[$product['Id']])) {
                $resultIds[] = $product['Id'];
                $boosts[] = [
                    'match' => [
                        '_id' => [
                            'query' => $product['Id'],
                            'boost' => $rank
                        ]
                    ]
                ];

                $rank--;
            }
        }

        return [
            'ids' => $resultIds,
            'boosts' => $boosts
        ];
    }

    /**
     * Processes any personalised products that are present
     * ready to remove them from the main search results as they'll be shown above the results
     *
     * @param mixed[] $result
     * @return mixed|null
     */
    public function processPersonalisedProducts($result)
    {
        $personalProductIds = [];
        if (isset($result['personalizedProducts']) && is_array($result['personalizedProducts'])) {

            foreach ($result['personalizedProducts'] as $product) {
                $personalProductIds[$product['Id']] = 1;
            }

            // reset personalised products if there's only the personalised products present
            if (count($result['products']) === count($personalProductIds)) {
                $personalProductIds = [];
            }
        }
        return $personalProductIds;
    }

    /**
     * Runs the PureClarity product search/listing request.
     *
     * @return mixed|null
     */
    public function getSearchResult()
    {
        if ($this->searchResult === null) {
            $this->service->dispatch();
            $this->searchResult = $this->service->getSearchResult();
        }

        return $this->searchResult;
    }

    /**
     * @return bool
     */
    public function isPureClarityEnabled()
    {
        return ($this->coreHelper->isSearchActive() || $this->coreHelper->isProdListingActive()) &&
            ($this->coreHelper->isServerSide() || $this->coreHelper->seoSearchFriendly()) &&
            ($this->isCategoryPage() || $this->isSearchPage());
    }

    /**
     * Returns whether this is the search page
     * @return bool
     */
    public function isSearchPage()
    {
        return $this->request->getFullActionName() === 'catalogsearch_result_index';
    }

    /**
     * Returns whether this is the category page
     * @return bool
     */
    public function isCategoryPage()
    {
        return ($this->request->getControllerName() === 'category');
    }
}
