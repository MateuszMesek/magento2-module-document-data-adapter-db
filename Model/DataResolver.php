<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model;

use Generator;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\DataResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\DimensionResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\ReadHandlerInterface;
use Traversable;

class DataResolver implements DataResolverInterface
{
    public function __construct(
        private readonly ReadHandlerInterface  $readHandler,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly FilterBuilder         $filterBuilder,
        private readonly int                   $batchSize = 100
    )
    {
    }

    public function resolve(array $dimensions, Traversable $entityIds): Traversable
    {
        $batches = $this->batchDocumentIds($entityIds);

        foreach ($batches as $documentIds) {
            $filters = [
                $this->filterBuilder
                    ->setField('document_id')
                    ->setConditionType('in')
                    ->setValue($documentIds)
                    ->create()
            ];

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilters($filters)
                ->create();

            $documents = iterator_to_array(
                $this->readHandler->readIndex($dimensions, $searchCriteria)
            );

            foreach ($documentIds as $documentId) {
                yield $documentId => $documents[$documentId] ?? null;
            }
        }
    }

    private function batchDocumentIds(Traversable $entityIds): Generator
    {
        $i = 0;
        $documentIds = [];

        foreach ($entityIds as $entityId) {
            $documentIds[] = $entityId;

            if (++$i !== $this->batchSize) {
                continue;
            }

            yield $documentIds;

            $i = 0;
            $documentIds = [];
        }

        if (!empty($documentIds)) {
            yield $documentIds;
        }
    }
}
