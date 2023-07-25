<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model;

use Magento\Framework\DB\Query\Generator as QueryGenerator;
use MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel\Index as Resource;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\IdsResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\IndexNameResolverInterface;
use Traversable;

class IdsResolver implements IdsResolverInterface
{
    public function __construct(
        private readonly IndexNameResolverInterface $indexNameResolver,
        private readonly Resource                   $resource,
        private readonly QueryGenerator             $queryGenerator,
        private readonly int                        $batchSize = 100
    )
    {
    }

    public function resolve(array $dimensions): Traversable
    {
        $indexName = $this->indexNameResolver->resolve($dimensions);
        $connection = $this->resource->getConnection();

        $select = ($connection->select())
            ->from($this->resource->getTable($indexName), [])
            ->columns([Resource::FIELD_DOCUMENT_ID])
            ->distinct(true);

        $documentIdsBatches = $this->queryGenerator->generate(
            Resource::FIELD_DOCUMENT_ID,
            $select,
            $this->batchSize
        );

        foreach ($documentIdsBatches as $documentIdsBatch) {
            yield from $connection->fetchCol($documentIdsBatch);
        }
    }
}
