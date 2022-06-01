<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Ext;

use Dubas\Console\Tool\CommandRunnerTool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ExtInitCommand extends Command
{
    public static string $messageOnConsoleStart = "Let's build an extension!";

    public static string $messageOnConsoleSuccess = 'Your environment is ready';

    protected static $defaultName = 'ext:init';

    protected static $defaultDescription = 'Setup a complete environment for developing an extension';

    public function __construct(private CommandRunnerTool $commandRunnerTool)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cmd = $this->commandRunnerTool->setApplication($this->getApplication());

        if (0 !== $cmd->runCommand('core:download --no-interaction --install', $output)) {
            return Command::FAILURE;
        }

        if (0 !== $cmd->runCommand('ext:install --working-dir=extensions/', $output)) {
            return Command::FAILURE;
        }

        if (0 !== $cmd->runCommand('ext:composer-install', $output)) {
            return Command::FAILURE;
        }

        if (0 !== $cmd->runCommand('ext:npm-install', $output)) {
            return Command::FAILURE;
        }

        if (0 !== $cmd->runCommand('ext:copy', $output)) {
            return Command::FAILURE;
        }

        if (0 !== $cmd->runCommand('ext:after-install', $output)) {
            return Command::FAILURE;
        }

        return $cmd->runCommand('admin:rebuild', $output);
    }
}
