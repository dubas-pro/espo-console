<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Core;

use Dubas\Console\Filesystem\FilesystemInterface;
use Dubas\Console\Process\ProcessInterface;
use Dubas\Console\Tool\CommandRunnerTool;
use Dubas\Console\Tool\NpmTool;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class CoreBuildCommand extends Command
{
    public static string $messageOnConsoleStart = 'Building EspoCRM instance';

    public static string $messageOnConsoleSuccess = 'EspoCRM build completed';

    protected static $defaultName = 'core:build';

    protected static $defaultDescription = 'Build EspoCRM instance';

    public function __construct(
        private PathUtil $pathUtil,
        private ProcessInterface $process,
        private FilesystemInterface $filesystem,
        private NpmTool $npmTool,
        private CommandRunnerTool $commandRunnerTool
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('build', '', InputOption::VALUE_OPTIONAL, '')
            ->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instancePath = $this->pathUtil->getInstancePath($input);
        $cmd = $this->commandRunnerTool->setApplication($this->getApplication());

        $gruntfileJsPath = $instancePath . '/Gruntfile.js';

        if (!file_exists($gruntfileJsPath)) {
            $output->writeln(
                sprintf(
                    '<comment>%s</>',
                    'Could not find a Gruntfile.js file in ' . PHP_EOL .
                    $instancePath . PHP_EOL . PHP_EOL .
                    'Nothing to build. Skipping.'
                )
            );

            return Command::INVALID;
        }

        $packageManager = $this->npmTool->getPackageManagerType();

        $output->writeln('Installing node modules with ' . $packageManager);

        if (0 !== $cmd->runCommand($packageManager . ':install --quiet --working-dir=' . $instancePath)) {
            return Command::FAILURE;
        }

        if ($packageManager === 'pnpm') {
            if ($this->npmTool->pnpmIsInstalled()) {
                $output->writeln('Updating Gruntfile.js to use pnpm...');

                $gruntfileJs = file_get_contents($gruntfileJsPath) ?: '';
                $gruntfileJs = str_replace(
                    'npm ci',
                    'pnpm install --frozen-lockfile',
                    $gruntfileJs
                );
                file_put_contents($gruntfileJsPath, $gruntfileJs);
            }
        }

        $output->writeln('Building with Grunt...');

        if (0 !== $this->runGruntCommand($instancePath, $input->getOption('build'), $output)) {
            return Command::FAILURE;
        }

        if ($this->filesystem->exists($instancePath . '/build')) {
            $this->filesystem->remove($instancePath . '/build');
        }

        return Command::SUCCESS;
    }

    private function runGruntCommand(string $workingDirectory, ?string $gruntBuildType = null, OutputInterface $output): int
    {
        $gruntProcessCommand = ['grunt'];

        if ($gruntBuildType && 'default' !== $gruntBuildType) {
            $gruntProcessCommand[] = $gruntBuildType;
        }

        try {
            $this->process
                ->execute($gruntProcessCommand)
                ->setWorkingDirectory($workingDirectory)
                ->mustRun();
        } catch (ProcessFailedException $e) {
            $output->write(
                sprintf('<error>%s</>', $e->getMessage())
            );

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
