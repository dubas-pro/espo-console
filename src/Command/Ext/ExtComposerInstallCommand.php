<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Ext;

use Dubas\Console\Tool\CommandRunnerTool;
use Dubas\Console\Tool\ExtensionTool;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ExtComposerInstallCommand extends Command
{
    public static string $messageOnConsoleStart = 'Installing Composer dependencies for an extension';

    public static string $messageOnConsoleSuccess = 'Composer dependencies installed';

    protected static $defaultName = 'ext:composer-install';

    protected static $defaultDescription = 'Composer dependencies for an extension';

    public function __construct(
        private PathUtil $pathUtil,
        private ExtensionTool $extensionTool,
        private CommandRunnerTool $commandRunnerTool
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('no-dev', '', InputOption::VALUE_NONE, 'Skip installing packages listed in require-dev');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $this->pathUtil->getWorkingDirectory($input);
        $applicationDirectory = $this->extensionTool->getApplicationDirectory();

        // Default to an extension application directory if
        // requested path is the same as working directory
        if ($workingDirectory === WORKING_DIRECTORY) {
            $workingDirectory = $applicationDirectory;
        }

        $command = 'composer:install --working-dir=' . $workingDirectory;

        if ($input->getOption('no-dev')) {
            $command .= ' --no-dev';
        }

        $exitCode = $this->commandRunnerTool
            ->setApplication($this->getApplication())
            ->runCommand($command, null);

        if (0 !== $exitCode) {
            $output->writeln(
                sprintf('<comment> %s</>', 'No Composer dependencies for an extension has been found')
            );
        }

        return Command::SUCCESS;
    }
}
