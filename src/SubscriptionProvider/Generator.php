<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\SubscriptionProvider;

use InvalidArgumentException;
use Magento\Framework\DB\Ddl\Trigger;
use MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel\Index as Resource;
use MateuszMesek\DocumentDataIndexIndexer\Dimension\Factory as DimensionFactory;
use MateuszMesek\DocumentDataIndexIndexer\DimensionProvider\WithDocumentNameProvider;
use MateuszMesek\DocumentDataIndexIndexer\DimensionProviderFactory;
use MateuszMesek\DocumentDataIndexIndexerApi\IndexNameResolverInterface;
use MateuszMesek\DocumentDataIndexMview\Data\SubscriptionFactory;
use Traversable;

class Generator
{
    private DimensionProviderFactory $dimensionProviderFactory;
    private DimensionFactory $dimensionFactory;
    private IndexNameResolverInterface $indexNameResolver;
    private Resource $resource;
    private SubscriptionFactory $subscriptionFactory;

    public function __construct(
        DimensionProviderFactory   $dimensionProviderFactory,
        DimensionFactory           $dimensionFactory,
        IndexNameResolverInterface $indexNameResolver,
        Resource                   $resource,
        SubscriptionFactory        $subscriptionFactory
    )
    {
        $this->dimensionProviderFactory = $dimensionProviderFactory;
        $this->dimensionFactory = $dimensionFactory;
        $this->indexNameResolver = $indexNameResolver;
        $this->resource = $resource;
        $this->subscriptionFactory = $subscriptionFactory;
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
