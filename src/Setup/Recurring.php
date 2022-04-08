<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use MateuszMesek\DocumentDataIndexerApi\Command\GetIndexNamesInterface;
use MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel\Index as Resource;

class Recurring implements InstallSchemaInterface
{
    private GetIndexNamesInterface $getIndexNames;
    private Resource $resource;

    public function __construct(
        GetIndexNamesInterface $getIndexNames,
        Resource               $resource
    )
    {
        $this->getIndexNames = $getIndexNames;
        $this->resource = $resource;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $connection = $this->resource->getConnection();

        $patternTableName = $this->resource->getMainTable();

        foreach ($this->getIndexNames->execute() as $indexName) {
            $indexTableName = $this->resource->getTable($indexName);

            if ($connection->isTableExists($indexTableName)) {
                continue;
            }

            $connection->query(sprintf(
                <<<SQL
                    CREATE TABLE IF NOT EXISTS %s LIKE %s
                SQL,
                $indexTableName,
                $patternTableName
            ));
        }
    }
}
