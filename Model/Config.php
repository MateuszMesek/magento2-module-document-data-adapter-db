<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model;

use Magento\Framework\App\DeploymentConfig;
use MateuszMesek\DocumentDataIndexIndexerApi\Model\Config\IndexNamePatternInterface;

class Config implements IndexNamePatternInterface
{
    public const DEPLOYMENT_CONFIG_INDEX_PATTERN = 'document_data/db/index_pattern';

    public function __construct(
        private readonly DeploymentConfig $deploymentConfig
    )
    {
    }

    public function getIndexNamePattern(string $documentName): string
    {
        return $this->deploymentConfig->get(
            self::DEPLOYMENT_CONFIG_INDEX_PATTERN,
            'document_data_{{document_name}}_{{store_id}}'
        );
    }
}
