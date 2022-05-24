<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Ext;

use Dubas\Console\Tool\CommandRunnerTool;
use Dubas\Console\Tool\ExtensionTool;
use Dubas\Console\Tool\NpmTool;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ExtNpmInstallCommand extends Command
{
    public static string $messageOnConsoleStart = 'Installing Node.js dependencies for an extension';

    public static string $messageOnConsoleSuccess = 'Node.js dependencies installed';

    protected static $defaultName = 'ext:npm-install';

    protected static $defaultDescription = 'Install Node.js dependencies for an extension';

    public function __construct(
        private PathUtil $pathUtil,
        private ExtensionTool $extensionTool,
        private NpmTool $npmTool,
        private CommandRunnerTool $commandRunnerTool
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $this->pathUtil->getWorkingDirectory($input);
        $clientDirectory = $this->extensionTool->getClientDirectory();

        // Default to an extension client directory if
        // requested path is the same as working directory
        if ($workingDirectory === WORKING_DIRECTORY) {
            $workingDirectory = $clientDirectory;
        }

        $packageManager = $this->npmTool
            ->setWorkingDirectory($clientDirectory)
            ->getPackageManagerType();

        $command = $packageManager . ':install --quiet --working-dir=' . $workingDirectory;

        $exitCode = $this->commandRunnerTool
            ->setApplication($this->getApplication())
            ->runCommand($command, null);

        if (0 !== $exitCode) {
            $output->writeln(
                sprintf('<comment> %s</>', 'No Node.js dependencies for an extension has been found')
            );
        }

        return Command::SUCCESS;
    }
}
