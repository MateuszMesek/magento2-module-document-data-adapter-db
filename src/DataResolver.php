<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB;

use Generator;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\ArrayManager;
use MateuszMesek\DocumentDataIndexIndexerApi\DataResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\DimensionResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\IndexNameResolverInterface;
use MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel\Index as Resource;
use Traversable;

class DataResolver implements DataResolverInterface
{
    private IndexNameResolverInterface $indexNameResolver;
    private DimensionResolverInterface $nodePathsResolver;
    private Resource $resource;
    private SerializerInterface $serializer;
    private ArrayManager $arrayManager;
    private int $batchSize;

    public function __construct(
        IndexNameResolverInterface $indexNameResolver,
        DimensionResolverInterface $nodePathsResolver,
        Resource                   $resource,
        SerializerInterface        $serializer,
        ArrayManager               $arrayManager,
        int                        $batchSize = 100
    )
    {
        $this->indexNameResolver = $indexNameResolver;
        $this->nodePathsResolver = $nodePathsResolver;
        $this->resource = $resource;
        $this->serializer = $serializer;
        $this->arrayManager = $arrayManager;
        $this->batchSize = $batchSize;
    }

    public function resolve(array $dimensions, Traversable $entityIds): Traversable
    {
        $batches = $this->batchDocumentIds($entityIds);
        $nodePaths = $this->nodePathsResolver->resolve($dimensions);

        foreach ($batches as $documentIds) {
            $connection = $this->resource->getConnection();

            $select = ($connection->select())
                ->from($this->getTableName($dimensions))
                ->where('document_id IN (?)', $documentIds);

            if (null !== $nodePaths) {
                $select->where('node_path IN (?)', $nodePaths);
            }

            $documents = [];

            $query = $connection->query($select);

            while ($row = $query->fetch()) {
                ['document_id' => $documentId, 'node_path' => $nodePath, 'node_value' => $nodeValue] = $row;

                $documents[$documentId] = $this->arrayManager->set(
                    $nodePath,
                    $documents[$documentId] ?? [],
                    $this->serializer->unserialize($nodeValue)
                );
            }

            yield from $documents;
        }
    }

    private function getTableName(array $dimensions): string
    {
        return $this->resource->getTable(
            $this->indexNameResolver->resolve($dimensions)
        );
    }

    private function batchDocumentIds(Traversable $entityIds): Generator
    {
        $i = 0;
        $documentIds = [];

        foreach ($entityIds as $entityId) {
            $documentIds[] = $entityId;

            if (++$i === $this->batchSize) {
                yield $documentIds;

                $i = 0;
                $documentIds = [];
            }
        }

        if (!empty($documentIds)) {
            yield $documentIds;
        }
    }
}
