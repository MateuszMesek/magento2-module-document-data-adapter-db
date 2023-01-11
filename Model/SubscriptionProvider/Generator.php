<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model\SubscriptionProvider;

use InvalidArgumentException;
use Magento\Framework\DB\Ddl\Trigger;
use MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel\Index as Resource;
use MateuszMesek\DocumentDataIndexIndexer\Model\Dimension\Factory as DimensionFactory;
use MateuszMesek\DocumentDataIndexIndexer\Model\DimensionProvider\WithDocumentNameProvider;
use MateuszMesek\DocumentDataIndexIndexer\Model\DimensionProviderFactory;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\IndexNameResolverInterface;
use MateuszMesek\DocumentDataIndexMview\Model\Data\SubscriptionFactory;
use Traversable;

class Generator
{
    public function __construct(
        private readonly DimensionProviderFactory   $dimensionProviderFactory,
        private readonly DimensionFactory           $dimensionFactory,
        private readonly IndexNameResolverInterface $indexNameResolver,
        private readonly Resource                   $resource,
        private readonly SubscriptionFactory        $subscriptionFactory
    )
    {
    }

    public function generate(string $documentName): Traversable
    {
        $dimensions = iterator_to_array(
            $this->dimensionProviderFactory->create($documentName)->getIterator()
        );

        foreach (Trigger::getListOfEvents() as $event) {
            switch ($event) {
                case Trigger::EVENT_INSERT:
                case Trigger::EVENT_UPDATE:
                    $prefix = 'NEW';
                    break;

                case Trigger::EVENT_DELETE:
                    $prefix = 'OLD';
                    break;

                default:
                    throw new InvalidArgumentException("Trigger event '$event' is unsupported");
            }

            foreach ($dimensions as $dimension) {
                $indexName = $this->getIndexName($documentName, $dimension);

                $tableName = $this->resource->getTable($indexName);

                $sqlDimensions = $this->dimensionToSQL($dimension);

                yield $this->subscriptionFactory->create([
                    'tableName' => $tableName,
                    'triggerEvent' => $event,
                    'rows' => <<<SQL
                        SELECT $prefix.document_id AS document_id,
                               $prefix.node_path AS node_path,
                               $sqlDimensions AS dimensions
                    SQL
                ]);
            }
        }
    }

    private function getIndexName(string $documentName, array $dimensions): string
    {
        $dimensions[WithDocumentNameProvider::DIMENSION_NAME] = $this->dimensionFactory->create(
            WithDocumentNameProvider::DIMENSION_NAME,
            $documentName
        );

        return $this->indexNameResolver->resolve($dimensions);
    }

    private function dimensionToSQL(array $dimensions): string
    {
        $sql = 'JSON_SET("{}"';

        foreach ($dimensions as $dimension) {
            $sql .= ', "$.' . $dimension->getName() . '"';
            $sql .= ', "' . $dimension->getValue() . '"';
        }

        $sql .= ')';

        return $sql;
    }
}
