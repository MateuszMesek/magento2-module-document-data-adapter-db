<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\IdsResolverInterface;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\ReadHandlerInterface;
use Traversable;

class IdsResolver implements IdsResolverInterface
{
    public function __construct(
        private readonly ReadHandlerInterface  $readHandler,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    )
    {
    }

    public function resolve(array $dimensions): Traversable
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->create();

        $documents = $this->readHandler->readIndex($dimensions, $searchCriteria);

        foreach ($documents as $documentId => $documentData) {
            yield $documentId;
        }
    }
}
