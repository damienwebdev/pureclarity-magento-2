<?php

namespace Pureclarity\Core\Model;

use Magento\Framework\Search\Adapter\Mysql;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Adapter\Mysql\Filter\Builder;
use Magento\Framework\Search\Adapter\Mysql\Query\Builder\Match;
use Magento\Framework\Search\Adapter\Mysql\Query\MatchContainer;
use Magento\Framework\Search\Adapter\Mysql\Query\QueryContainer;
use Magento\Framework\Search\Adapter\Mysql\Query\QueryContainerFactory;
use Magento\Framework\Search\EntityMetadata;
use Magento\Framework\Search\Request\Query\BoolExpression as BoolQuery;
use Magento\Framework\Search\Request\Query\Filter as FilterQuery;
use Magento\Framework\Search\Request\Query\Match as MatchQuery;
use Magento\Framework\Search\Request\QueryInterface as RequestQueryInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Adapter\Mysql\ScoreBuilderFactory;
use Magento\Framework\Search\Adapter\Mysql\ScoreBuilder;
use Magento\Framework\Search\Adapter\Mysql\ConditionManager;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory;

class Mapper extends \Magento\Framework\Search\Adapter\Mysql\Mapper
{

    private $indexProviders;
    private $queryContainerFactory;
    private $scoreBuilderFactory;
    private $resource;
    private $entityMetadata;
    private $relevanceCalculationMethod;

    public function __construct(
        ScoreBuilderFactory $scoreBuilderFactory,
        Builder $filterBuilder,
        ConditionManager $conditionManager,
        ResourceConnection $resource,
        EntityMetadata $entityMetadata,
        QueryContainerFactory $queryContainerFactory,
        Match $matchBuilder,
        TemporaryStorageFactory $temporaryStorageFactory,
        array $indexProviders,
        $relevanceCalculationMethod = 'SUM'
    ) {
        $this->indexProviders = $indexProviders;
        $this->queryContainerFactory = $queryContainerFactory;
        $this->scoreBuilderFactory = $scoreBuilderFactory;
        $this->resource = $resource;
        $this->entityMetadata = $entityMetadata;
        $this->relevanceCalculationMethod = $relevanceCalculationMethod;
        parent::__construct(
            $scoreBuilderFactory,
            $filterBuilder,
            $conditionManager,
            $resource,
            $entityMetadata,
            $queryContainerFactory,
            $matchBuilder,
            $temporaryStorageFactory,
            $indexProviders
        );
    }


    public function buildQuery(RequestInterface $request, $tableName = 'ian_test')
    {
        if (!array_key_exists($request->getIndex(), $this->indexProviders)) {
            throw new \LogicException('Index provider not configured');
        }

        $indexBuilder = $this->indexProviders[$request->getIndex()];

        $queryContainer = $this->queryContainerFactory->create(
            [
                'indexBuilder' => $indexBuilder,
                'request' => $request,
            ]
        );
        $select = $indexBuilder->build($request);
        /** @var ScoreBuilder $scoreBuilder */
        $scoreBuilder = $this->scoreBuilderFactory->create();
        $select = $this->processQuery(
            $scoreBuilder,
            $request->getQuery(),
            $select,
            BoolQuery::QUERY_CONDITION_MUST,
            $queryContainer
        );
        
        $select->columns('pc.score AS score');
        $select->joinInner(
            ['pc' => $tableName],
            'pc.entity_id = search_index.entity_id',
            []
        );
        $select = $this->createAroundSelect($select, $scoreBuilder, 0);

        $select->limit($request->getSize(), $request->getFrom());
        $select->order('relevance ' . Select::SQL_DESC)->order('entity_id ' . Select::SQL_DESC);
        return $select;
    }


    private function createAroundSelect(Select $select, ScoreBuilder $scoreBuilder)
    {
        $parentSelect = $this->resource->getConnection()->select();
        $parentSelect->from(
            ['main_select' => $select],
            [
                $this->entityMetadata->getEntityId() => 'entity_id',
                'relevance' => sprintf('%s(%s)', $this->relevanceCalculationMethod, $scoreBuilder->getScoreAlias()),
            ]
        )->group($this->entityMetadata->getEntityId());
        return $parentSelect;
    }
}
