<?php

declare(strict_types=1);

namespace Dubas\Console\Process;

use Symfony\Component\Process\Process as SymfonyProcess;

class Process implements ProcessInterface
{
    public function execute(array $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 300): SymfonyProcess
    {
        return new SymfonyProcess($command, $cwd, $env, $input, $timeout);
    }
}
