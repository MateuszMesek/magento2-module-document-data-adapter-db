<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model;

use Magento\Framework\Serialize\SerializerInterface;
use MateuszMesek\DocumentDataApi\Model\Command\GetDocumentNodesInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\DimensionResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\IndexNameResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\SaveHandlerInterface;
use MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel\Index as Resource;
use Traversable;

class SaveHandler implements SaveHandlerInterface
{
    public function __construct(
        private readonly IndexNameResolverInterface $indexNameResolver,
        private readonly DimensionResolverInterface $documentNameResolver,
        private readonly DimensionResolverInterface $nodePathsResolver,
        private readonly GetDocumentNodesInterface  $getDocumentNodes,
        private readonly Resource                   $resource,
        private readonly SerializerInterface        $serializer
    )
    {
    }

    public function isAvailable(array $dimensions): bool
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
            $paths[] = $documentNode->getPath();
        }

        $documentIds = [];
        $documentRows = [];

        foreach ($documents as $documentId => $document) {
            /** @var \MateuszMesek\DocumentDataApi\Model\Data\DocumentDataInterface $document */
            $documentId = (string)$documentId;

            $documentIds[] = $documentId;

            foreach ($paths as $path) {
                if ($nodePaths && !in_array($path, $nodePaths, true)) {
                    continue;
                }

                $documentRows[] = [
                    'document_id' => $documentId,
                    'node_path' => $path,
                    'node_value' => $this->serializer->serialize(
                        $document->get($path)
                    )
                ];
            }
        }

        $connection = $this->resource->getConnection();
        $connection->delete(
            $this->getTableName($dimensions),
            [
                'document_id IN (?)' => $documentIds,
                'node_path NOT IN (?)' => $paths
            ]
        );

        if (empty($documentRows)) {
            return;
        }

        $connection->insertOnDuplicate(
            $this->getTableName($dimensions),
            $documentRows,
            ['node_value']
        );
    }

    public function deleteIndex($dimensions, Traversable $documents): void
    {
        $documentIds = [];

        foreach ($documents as $documentId => $document) {
            $documentIds[] = (string)$documentId;
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
        $indexName = $this->indexNameResolver->resolve($dimensions);

        return $this->resource->getTable(
            $indexName
        );
    }
}
