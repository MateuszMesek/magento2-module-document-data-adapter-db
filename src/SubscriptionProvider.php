<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB;

use MateuszMesek\DocumentDataAdapterDB\SubscriptionProvider\Generator;
use MateuszMesek\DocumentDataIndexMview\ContextReader;
use MateuszMesek\DocumentDataIndexMviewApi\SubscriptionProviderInterface;
use Traversable;

class SubscriptionProvider implements SubscriptionProviderInterface
{
    private ContextReader $contextReader;

    public function __construct(
        ContextReader $contextReader
    )
    {
        $this->contextReader = $contextReader;
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
