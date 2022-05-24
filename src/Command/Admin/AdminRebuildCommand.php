<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Admin;

use Dubas\Console\Process\ProcessInterface;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class AdminRebuildCommand extends Command
{
    protected static $defaultName = 'admin:rebuild';

    protected static $defaultDescription = 'Rebuild backend and clear cache';

    public function __construct(
        private PathUtil $pathUtil,
        private ProcessInterface $process
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setAliases(['rb']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instancePath = $this->pathUtil->getInstancePath($input);

        $process = $this->process
            ->execute(['php', 'bin/command', 'rebuild'])
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
