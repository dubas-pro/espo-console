<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Db;

use Dubas\Console\Process\ProcessInterface;
use Dubas\Console\Util\ConfigUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class DbQueryCommand extends Command
{
    public static string $messageOnConsoleStart = 'Executing a SQL query';

    public static string $messageOnConsoleSuccess = 'SQL query executed';

    protected static $defaultName = 'db:query';

    protected static $defaultDescription = 'Executes a SQL query against the database';

    public function __construct(
        private ConfigUtil $configUtil,
        private ProcessInterface $process
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('sql-query', InputArgument::REQUIRED, 'A SQL query');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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
                '--database=' . $configData['database']['dbname'],
                '--password=' . $configData['database']['password'],
                '--execute=' . $input->getArgument('sql-query'),
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
