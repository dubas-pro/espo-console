<?php

declare(strict_types=1);

namespace Dubas\Console\Process;

use Symfony\Component\Process\Process;

interface ProcessInterface
{
    /**
     * @param array<string> $command
     * @param array<string|\Stringable> $env
     * @param mixed $input
     */
    public function execute(array $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 300): Process;
}
