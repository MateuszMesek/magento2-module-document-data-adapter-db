<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model;

use MateuszMesek\DocumentDataAdapterDB\Model\SubscriptionProvider\Generator;
use MateuszMesek\DocumentDataIndexMview\Model\ContextReader;
use MateuszMesek\DocumentDataIndexMviewApi\Model\SubscriptionProviderInterface;
use Traversable;

class SubscriptionProvider implements SubscriptionProviderInterface
{
    public function __construct(
        private readonly ContextReader $contextReader
    )
    {
    }

    public function get(array $context): Traversable
    {
        $documentName = $this->contextReader->getDocumentName($context);

        yield '*' => [
            'sync' => [
                'id' => 'sync',
                'type' => Generator::class,
                'arguments' => [
                    'documentName' => $documentName
                ]
            ]
        ];
    }
}
