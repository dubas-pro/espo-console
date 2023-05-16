<?php

declare(strict_types=1);

namespace Dubas\Console\Espo;

use Espo\Core\Container;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;

interface EspoManagerInterface
{
    public function setWorkingDirectory(string $workingDirectory): self;

    public function getContainer(): Container;

    public function getConfig(): Config;

    public function createConfigWriter(): ConfigWriter;

    public function merge($data, $overrideData);

    public function importEntities(array $data = []): void;
}
