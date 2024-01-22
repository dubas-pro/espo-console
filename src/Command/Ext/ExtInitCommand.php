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

        $cmd->runCommand('core:download --no-interaction', $output);
        $cmd->runCommand('core:build', $output);
        $cmd->runCommand('core:install', $output);
        $cmd->runCommand('ext:install --working-dir=extensions/', $output);
        $cmd->runCommand('ext:composer-install', $output);
        $cmd->runCommand('ext:npm-install', $output);
        $cmd->runCommand('ext:copy', $output);
        $cmd->runCommand('ext:after-install', $output);

        return $cmd->runCommand('admin:rebuild', $output);
    }
}
