<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model;

use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\DB\Query\Generator as QueryGenerator;
use Magento\Framework\DB\Select;
use Magento\Framework\Serialize\SerializerInterface;
use MateuszMesek\DocumentData\Model\Data\DocumentDataFactory;
use MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel\Index as Resource;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\IndexNameResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\ReadHandlerInterface;
use Traversable;

class ReadHandler extends AbstractHandler implements ReadHandlerInterface
{
    private const FILTER_FIELDS = [
        Resource::FIELD_DOCUMENT_ID,
        Resource::FIELD_NODE_PATH,
        Resource::FIELD_NODE_VALUE
    ];

    public function __construct(
        Resource                             $resource,
        IndexNameResolverInterface           $indexNameResolver,
        private readonly QueryGenerator      $queryGenerator,
        private readonly DocumentDataFactory $documentDataFactory,
        private readonly SerializerInterface $serializer,
        private readonly int                 $batchSize = 100
    )
    {
        parent::__construct(
            $resource,
            $indexNameResolver
        );
    }

    public function readIndex(array $dimensions, ?SearchCriteriaInterface $searchCriteria = null): Traversable
    {
        $connection = $this->resource->getConnection();

        $select = ($connection->select())
            ->from($this->getTableName($dimensions), []);

        if (null !== $searchCriteria) {
            $this->addFilterToSelect($searchCriteria, $select);
            $this->addPageToSelect($searchCriteria, $select);
            $this->addSortToSelect($searchCriteria, $select);
        }

        $documentIdsSelect = (clone $select)
            ->distinct(true)
            ->columns([Resource::FIELD_DOCUMENT_ID]);

        $documentIdsBatches = $this->queryGenerator->generate(
            Resource::FIELD_DOCUMENT_ID,
            $documentIdsSelect,
            $this->batchSize
        );

        foreach ($documentIdsBatches as $documentIdsBatch) {
            $ids = $connection->fetchCol($documentIdsBatch);

            $dataSelect = (clone $select)
                ->columns([Resource::FIELD_DOCUMENT_ID, Resource::FIELD_NODE_PATH, Resource::FIELD_NODE_VALUE])
                ->where($connection->prepareSqlCondition(
                    Resource::FIELD_DOCUMENT_ID,
                    ['in' => $ids]
                ));

            $dataQuery = $connection->query($dataSelect);

            $documents = [];

            while ($row = $dataQuery->fetch()) {
                [Resource::FIELD_DOCUMENT_ID => $documentId, Resource::FIELD_NODE_PATH => $nodePath, Resource::FIELD_NODE_VALUE => $nodeValue] = $row;

                if (!isset($documents[$documentId])) {
                    $documents[$documentId] = $this->documentDataFactory->create();
                }

                $documents[$documentId]->set(
                    $nodePath,
                    $this->serializer->unserialize($nodeValue)
                );
            }

            yield from $documents;
        }
    }

    private function addFilterToSelect(SearchCriteriaInterface $searchCriteria, Select $select): void
    {
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $select->orWhere(
                $this->convertFilterGroupToCondition($filterGroup)
            );
        }
    }

    private function convertFilterGroupToCondition(FilterGroup $filterGroup): string
    {
        $connection = $this->resource->getConnection();
        $conditions = [];
        $filters = $filterGroup->getFilters();

        while ($filter = array_shift($filters)) {
            if (!in_array($filter->getField(), self::FILTER_FIELDS, true)) {
                continue;
            }

            $conditions[] = $connection->prepareSqlCondition(
                $filter->getField(),
                [$filter->getConditionType() => $filter->getValue()]
            );
        }

        return implode(' AND ', $conditions);
    }

    private function addPageToSelect(SearchCriteriaInterface $searchCriteria, Select $select): void
    {
        $page = $searchCriteria->getCurrentPage();
        $size = $searchCriteria->getPageSize();

        if (null === $page && null === $size) {
            return;
        }

        $select->limitPage(
            (int)$page,
            (int)$size
        );
    }

    private function addSortToSelect(SearchCriteriaInterface $searchCriteria, Select $select): void
    {
        $sortOrders = $searchCriteria->getSortOrders();

        if (null === $sortOrders) {
            return;
        }

        $connection = $this->resource->getConnection();

        foreach ($sortOrders as $sortOrder) {
            $select->order(
                sprintf(
                    '%s %s',
                    $connection->quoteIdentifier($sortOrder->getField()),
                    $connection->quote($sortOrder->getDirection())
                )
            );
        }
    }
}
