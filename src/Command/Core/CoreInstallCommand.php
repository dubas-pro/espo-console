<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Core;

use Dubas\Console\Filesystem\FilesystemInterface;
use Dubas\Console\Process\ProcessInterface;
use Dubas\Console\Tool\CommandRunnerTool;
use Dubas\Console\Util\ConfigUtil;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CoreInstallCommand extends Command
{
    public static string $messageOnConsoleStart = 'Installing EspoCRM instance';

    public static string $messageOnConsoleSuccess = 'EspoCRM installed';

    protected static $defaultName = 'core:install';

    protected static $defaultDescription = 'Run the standard EspoCRM installation process';

    public function __construct(
        private PathUtil $pathUtil,
        private FilesystemInterface $filesystem,
        private CommandRunnerTool $commandRunnerTool,
        private ConfigUtil $configUtil,
        private ProcessInterface $process,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dev-mode', '', InputOption::VALUE_NONE, 'Toggle developer mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (0 !== $this->doExecute($input, $output)) {
            return Command::FAILURE;
        }

        return $this->afterExecute($input, $output);
    }

    private function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $instancePath = $this->pathUtil->getInstancePath($input);
        $cmd = $this->commandRunnerTool->setApplication($this->getApplication());

        if (0 !== $cmd->runCommand('db:create', $output)) {
            return Command::FAILURE;
        }

        $configCreateCommand = 'config:create';
        if ($input->getOption('dev-mode')) {
            $configCreateCommand .= ' --dev-mode';
        }

        if (0 !== $cmd->runCommand($configCreateCommand, $output)) {
            return Command::FAILURE;
        }

        if ($this->filesystem->exists($instancePath . '/install/config.php')) {
            $this->filesystem->remove($instancePath . '/install/config.php');
        }

        $configData = $this->configUtil->getConfigData();

        $output->writeln('Install: step1...');
        $this->process
            ->execute([
                'php', 'install/cli.php', '-a', 'step1', '-d', 'user-lang=' . $configData['install']['language'],
            ])
            ->setWorkingDirectory($instancePath)
            ->mustRun();

        $output->writeln('Install: setupConfirmation...');
        $this->process
            ->execute([
                'php', 'install/cli.php', '-a', 'setupConfirmation',
                '-d', 'host-name=' . $configData['database']['host'] . ':' . $configData['database']['port'] .
                    '&db-name=' . $configData['database']['dbname'] .
                    '&db-user-name=' . $configData['database']['user'] .
                    '&db-user-password=' . $configData['database']['password'],
            ])
            ->setWorkingDirectory($instancePath)
            ->mustRun();

        $output->writeln('Install: checkPermission...');
        $this->process
            ->execute([
                'php', 'install/cli.php', '-a', 'checkPermission',
            ])
            ->setWorkingDirectory($instancePath)
            ->mustRun();

        $output->writeln('Install: saveSettings...');
        $this->process
            ->execute([
                'php', 'install/cli.php', '-a', 'saveSettings',
                '-d', 'site-url=' . $configData['install']['siteUrl'] .
                    '&default-permissions-user=' . (int) $configData['install']['defaultOwner'] .
                    '&default-permissions-group=' . $configData['install']['defaultGroup'],
            ])
            ->setWorkingDirectory($instancePath)
            ->mustRun();

        $output->writeln('Install: buildDatabase...');
        $this->process
            ->execute([
                'php', 'install/cli.php', '-a', 'buildDatabase',
            ])
            ->setWorkingDirectory($instancePath)
            ->mustRun();

        $output->writeln('Install: createUser...');
        $this->process
            ->execute([
                'php', 'install/cli.php', '-a', 'createUser',
                '-d', 'user-name=' . $configData['install']['adminUsername'] .
                    '&user-pass=' . $configData['install']['adminPassword'],
            ])
            ->setWorkingDirectory($instancePath)
            ->mustRun();

        $output->writeln('Install: finish...');
        $this->process
            ->execute([
                'php', 'install/cli.php', '-a', 'finish',
            ])
            ->setWorkingDirectory($instancePath)
            ->mustRun();

        return Command::SUCCESS;
    }

    private function afterExecute(InputInterface $input, OutputInterface $output): int
    {
        $cmd = $this->commandRunnerTool->setApplication($this->getApplication());

        if (0 !== $cmd->runCommand('config:merge', $output)) {
            return Command::FAILURE;
        }

        return $cmd->runCommand('admin:clear-cache', $output);
    }
}
