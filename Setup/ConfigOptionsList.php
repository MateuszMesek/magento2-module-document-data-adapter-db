<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Setup;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\Setup\Option\TextConfigOption;
use MateuszMesek\DocumentDataAdapterDB\Model\Config;

class ConfigOptionsList implements ConfigOptionsListInterface
{
    private const INPUT_INDEX_PATTERN = 'document-data-db-index-pattern';

    public function getOptions()
    {
        return [
            new TextConfigOption(
                self::INPUT_INDEX_PATTERN,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                Config::DEPLOYMENT_CONFIG_INDEX_PATTERN
            ),
        ];
    }

    public function createConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);

        if (isset($options[self::INPUT_INDEX_PATTERN])) {
            $configData->set(Config::DEPLOYMENT_CONFIG_INDEX_PATTERN, $options[self::INPUT_INDEX_PATTERN]);
        }

        return [$configData];
    }

    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        return [];
    }
}
