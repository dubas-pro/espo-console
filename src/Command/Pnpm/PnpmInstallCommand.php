<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Pnpm;

use Dubas\Console\Filesystem\FilesystemInterface;
use Dubas\Console\Process\ProcessInterface;
use Dubas\Console\Tool\NpmTool;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class PnpmInstallCommand extends Command
{
    protected static $defaultName = 'pnpm:install';

    protected static $defaultDescription = 'Install Node.js dependencies with PNPM';

    public function __construct(
        private FilesystemInterface $filesystem,
        private PathUtil $pathUtil,
        private NpmTool $npmTool,
        private ProcessInterface $process
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $this->pathUtil->getWorkingDirectory($input);

        if (!$this->filesystem->exists($workingDirectory . '/package.json')) {
            $output->writeln(
                sprintf('<comment>%s</>', 'Could not find a package.json file in ' . $workingDirectory)
            );

            return Command::INVALID;
        }

        if ($this->filesystem->exists($workingDirectory . '/node_modules')) {
            $this->filesystem->remove($workingDirectory . '/node_modules');
        }

        if (!$this->npmTool->pnpmIsInstalled()) {
            $output->write(
                sprintf('<error>%s</>', 'Could not install pnpm.')
            );

            return Command::FAILURE;
        }

        try {
            $this->process
                ->execute(['pnpm', 'install'])
                ->setWorkingDirectory($workingDirectory)
                ->disableOutput()
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
