<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model;

use MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel\Index as Resource;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\IndexNameResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\IndexStructureBuilderInterface;

class IndexStructureBuilder implements IndexStructureBuilderInterface
{
    public function __construct(
        private readonly IndexNameResolverInterface $indexNameResolver,
        private readonly Resource                   $resource,
    )
    {
    }

    public function build(array $dimensions = []): void
    {
        $indexTableName = $this->resource->getTable(
            $this->indexNameResolver->resolve($dimensions)
        );

        $connection = $this->resource->getConnection();

        if ($connection->isTableExists($indexTableName)) {
            return;
        }

        $patternTableName = $this->resource->getMainTable();

        $connection->query(sprintf(
            <<<SQL
                CREATE TABLE IF NOT EXISTS %s LIKE %s
            SQL,
            $connection->quoteIdentifier($indexTableName),
            $connection->quoteIdentifier($patternTableName)
        ));
    }
}
