<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use MateuszMesek\DocumentDataAdapterDB\Command\CreateIndexTables;

class Recurring implements InstallSchemaInterface
{
    private CreateIndexTables $createIndexTables;

    public function __construct(
        CreateIndexTables $createIndexTables
    )
    {
        $this->createIndexTables = $createIndexTables;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $this->createIndexTables->execute();
    }
}
