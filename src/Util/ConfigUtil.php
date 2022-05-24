<?php

declare(strict_types=1);

namespace Dubas\Console\Util;

class ConfigUtil
{
    /**
     * @return array<string,mixed>
     */
    public function getConfigData(): array
    {
        $workingDirectory = WORKING_DIRECTORY;

        // Load config-default.json
        $configDefault = $workingDirectory . '/config-default.json';

        if (!file_exists($configDefault)) {
            return [];
        }

        $configDefault = file_get_contents($configDefault);

        if ($configDefault === false) {
            return [];
        }

        $configDefault = json_decode($configDefault, true, 512, JSON_THROW_ON_ERROR);

        // Load config.json
        $config = $workingDirectory . '/config.json';
        if (file_exists($config) && file_get_contents($config) !== false) {
            $config = json_decode(file_get_contents($config), true, 512, JSON_THROW_ON_ERROR);

            $configDefault = array_replace_recursive($configDefault, $config);
        }

        return $configDefault;
    }
}
