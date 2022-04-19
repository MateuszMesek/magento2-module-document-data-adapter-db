<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\ArrayManager;
use MateuszMesek\DocumentDataApi\Command\GetDocumentNodesInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\DimensionResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\IndexNameResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\SaveHandlerInterface;
use MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel\Index as Resource;
use Traversable;

class SaveHandler implements SaveHandlerInterface
{
    private IndexNameResolverInterface $indexNameResolver;
    private DimensionResolverInterface $documentNameResolver;
    private DimensionResolverInterface $nodePathsResolver;
    private GetDocumentNodesInterface $getDocumentNodes;
    private Resource $resource;
    private ArrayManager $arrayManager;
    private SerializerInterface $serializer;

    public function __construct(
        IndexNameResolverInterface $indexNameResolver,
        DimensionResolverInterface $documentNameResolver,
        DimensionResolverInterface $nodePathsResolver,
        GetDocumentNodesInterface  $getDocumentNodes,
        Resource                   $resource,
        ArrayManager               $arrayManager,
        SerializerInterface        $serializer
    )
    {
        $this->indexNameResolver = $indexNameResolver;
        $this->documentNameResolver = $documentNameResolver;
        $this->nodePathsResolver = $nodePathsResolver;
        $this->getDocumentNodes = $getDocumentNodes;
        $this->resource = $resource;
        $this->arrayManager = $arrayManager;
        $this->serializer = $serializer;
    }

    public function isAvailable($dimensions = []): bool
    {
        return $this->resource->getConnection()->isTableExists(
            $this->getTableName($dimensions)
        );
    }

    public function saveIndex($dimensions, Traversable $documents): void
    {
        $documentName = $this->documentNameResolver->resolve($dimensions);
        $nodePaths = $this->nodePathsResolver->resolve($dimensions);

        $documentNodes = $this->getDocumentNodes->execute($documentName);

        $paths = [];

        foreach ($documentNodes as $documentNode) {
            $paths[] = $documentNode['path'];
        }

        $connection = $this->resource->getConnection();

        foreach ($documents as $documentId => $document) {
            $data = [];

            foreach ($paths as $path) {
                if ($nodePaths && !in_array($path, $nodePaths, true)) {
                    continue;
                }

                $data[] = [
                    'document_id' => $documentId,
                    'node_path' => $path,
                    'node_value' => $this->serializer->serialize(
                        $this->arrayManager->get($path, $document)
                    )
                ];
            }

            $connection->beginTransaction();

            $connection->delete(
                $this->getTableName($dimensions),
                [
                    'document_id = ?' => $documentId,
                    'node_path NOT IN (?)' => $paths
                ]
            );
            $connection->insertOnDuplicate(
                $this->getTableName($dimensions),
                $data,
                ['node_value']
            );

            $connection->commit();
        }
    }

    public function deleteIndex($dimensions, Traversable $documents): void
    {
        $documentIds = [];

        foreach ($documents as $document) {
            $documentIds[] = $document['id'];
        }

        $connection = $this->resource->getConnection();
        $connection->delete(
            $this->getTableName($dimensions),
            [
                'document_id IN (?)' => $documentIds
            ]
        );
    }

    private function getTableName(array $dimensions): string
    {
        return $this->resource->getTable(
            $this->indexNameResolver->resolve($dimensions)
        );
    }
}
