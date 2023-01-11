<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model;

use MateuszMesek\DocumentDataIndexIndexerApi\Model\IndexNameResolverInterface;
use MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel\Index as Resource;

abstract class AbstractHandler
{
    public function __construct(
        protected readonly Resource                   $resource,
        private readonly IndexNameResolverInterface $indexNameResolver
    )
    {
    }

    public function isAvailable($dimensions = []): bool
    {
        return $this->resource->getConnection()->isTableExists(
            $this->getTableName($dimensions)
        );
    }

    protected function getTableName(array $dimensions): string
    {
        $indexName = $this->indexNameResolver->resolve($dimensions);

        return $this->resource->getTable(
            $indexName
        );
    }
}
