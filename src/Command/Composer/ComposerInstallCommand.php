<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Composer;

use Dubas\Console\Filesystem\FilesystemInterface;
use Dubas\Console\Process\ProcessInterface;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class ComposerInstallCommand extends Command
{
    public static string $messageOnConsoleStart = 'Running composer install';

    public static string $messageOnConsoleSuccess = 'Composer packages installed';

    protected static $defaultName = 'composer:install';

    protected static $defaultDescription = 'Install Composer dependencies';

    public function __construct(
        private PathUtil $pathUtil,
        private FilesystemInterface $filesystem,
        private ProcessInterface $process
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('no-dev', '', InputOption::VALUE_NONE, 'Skip installing packages listed in require-dev')
            ->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $this->pathUtil->getWorkingDirectory($input);

        if (!$this->filesystem->exists($workingDirectory . '/composer.json')) {
            $output->writeln(
                sprintf('<comment>%s</>', 'Could not find a composer.json file in ' . $workingDirectory)
            );

            return Command::INVALID;
        }

        if ($this->filesystem->exists($workingDirectory . '/vendor')) {
            $this->filesystem->remove($workingDirectory . '/vendor');
        }

        $command = ['composer', 'install', '--ignore-platform-reqs'];

        if ($input->getOption('no-dev')) {
            $command[] = '--no-dev';
        }

        $process = $this->process
            ->execute($command)
            ->setWorkingDirectory($workingDirectory);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $output->write(
                sprintf('<error>%s</>', $e->getMessage())
            );

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
