<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Db;

use Dubas\Console\Process\ProcessInterface;
use Dubas\Console\Util\ConfigUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class DbDropCommand extends Command
{
    protected static $defaultName = 'db:drop';

    protected static $defaultDescription = 'Delete database';

    public function __construct(
        private ConfigUtil $configUtil,
        private ProcessInterface $process,
        private SymfonyStyle $symfonyStyle
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('yes', '', InputOption::VALUE_NONE, 'Answer yes to the confirmation message.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (
            !$input->getOption('yes')
            && $this->symfonyStyle->confirm('Are you sure you want to delete the database?', false) !== true
        ) {
            $output->writeln(
                sprintf('<error>%s</>', 'Aborted')
            );

            return Command::INVALID;
        }

        $configData = $this->configUtil->getConfigData();

        $port = '3306';
        if (!empty($configData['database']['port'])) {
            $port = $configData['database']['port'];
        }

        $process = $this->process
            ->execute([
                'mysql',
                '--host=' . $configData['database']['host'],
                '--port=' . $port,
                '--user=' . $configData['database']['user'],
                '--password=' . $configData['database']['password'],
                '--execute=DROP DATABASE IF EXISTS `' . $configData['database']['dbname'] . '`;',
            ]);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $output->writeln(
                sprintf('<error>%s</>', $e->getMessage())
            );

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
