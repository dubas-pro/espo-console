<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Ext;

use Dubas\Console\Tool\CommandRunnerTool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ExtAfterInstallCommand extends Command
{
    protected static $defaultName = 'ext:after-install';

    protected static $defaultDescription = 'Run after-install script';

    public function __construct(private CommandRunnerTool $commandRunnerTool)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cmd = $this->commandRunnerTool->setApplication($this->getApplication());

        return $cmd->runCommand('ext:run-script -s AfterInstall', $output);
    }
}
