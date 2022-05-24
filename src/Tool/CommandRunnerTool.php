<?php

declare(strict_types=1);

namespace Dubas\Console\Tool;

use Dubas\Console\Application;
use LogicException;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Output\OutputInterface;

class CommandRunnerTool
{
    private SymfonyApplication $application;

    public function setApplication(SymfonyApplication $application = null): self
    {
        if (null === $application) {
            throw new LogicException(sprintf('Cannot retrieve "%s" because there is no Application defined.', SymfonyApplication::class));
        }

        $this->application = $application;

        return $this;
    }

    public function runCommand(string $command, OutputInterface $output = null): int
    {
        /** @var Application $application */
        $application = $this->application;

        return $application->runCommand($command, $output);
    }
}
