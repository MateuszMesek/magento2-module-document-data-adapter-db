<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Observer\CreateIndexTables;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MateuszMesek\DocumentDataAdapterDB\Command\CreateIndexTables;

class OnStoreAdd implements ObserverInterface
{
    private CreateIndexTables $createIndexTables;

    public function __construct(
        CreateIndexTables $createIndexTables
    )
    {
        $this->createIndexTables = $createIndexTables;
    }

    public function execute(Observer $observer)
    {
        $this->createIndexTables->execute();
    }
}
