<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB;

use Generator;
use Magento\Framework\Serialize\SerializerInterface;
use MateuszMesek\DocumentData\Data\DocumentDataFactory;
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
    private DocumentDataFactory $documentDataFactory;
    private SerializerInterface $serializer;
    private int $batchSize;

    public function __construct(
        IndexNameResolverInterface $indexNameResolver,
        DimensionResolverInterface $nodePathsResolver,
        Resource                   $resource,
        DocumentDataFactory        $documentDataFactory,
        SerializerInterface        $serializer,
        int                        $batchSize = 100
    )
    {
        $this->indexNameResolver = $indexNameResolver;
        $this->nodePathsResolver = $nodePathsResolver;
        $this->resource = $resource;
        $this->documentDataFactory = $documentDataFactory;
        $this->serializer = $serializer;
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

            foreach ($documentIds as $documentId) {
                $documents[$documentId] = null;
            }

            $query = $connection->query($select);

            while ($row = $query->fetch()) {
                ['document_id' => $documentId, 'node_path' => $nodePath, 'node_value' => $nodeValue] = $row;

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
