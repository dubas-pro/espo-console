<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Admin;

use Dubas\Console\Process\ProcessInterface;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class AdminClearCacheCommand extends Command
{
    protected static $defaultName = 'admin:clear-cache';

    protected static $defaultDescription = 'Clear all backend cache';

    public function __construct(
        private PathUtil $pathUtil,
        private ProcessInterface $process
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setAliases(['cc']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instancePath = $this->pathUtil->getInstancePath($input);

        $process = $this->process
            ->execute(['php', 'bin/command', 'clear-cache'])
            ->setWorkingDirectory($instancePath);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $output->write(
                sprintf('<error>%s</>', $e->getMessage())
            );

            return Command::FAILURE;
        }

        $output->write(
            sprintf('<info>%s</>', $process->getOutput())
        );

        return Command::SUCCESS;
    }
}
