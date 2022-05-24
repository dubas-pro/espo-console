<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Db;

use Dubas\Console\Process\ProcessInterface;
use Dubas\Console\Util\ConfigUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class DbCreateCommand extends Command
{
    public static string $messageOnConsoleStart = 'Creating database';

    public static string $messageOnConsoleSuccess = 'Database created';

    protected static $defaultName = 'db:create';

    protected static $defaultDescription = 'Create database';

    public function __construct(
        private ConfigUtil $configUtil,
        private ProcessInterface $process
    ) {
        parent::__construct();
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
                '--password=' . $configData['database']['password'],
                '--execute=CREATE DATABASE IF NOT EXISTS `' . $configData['database']['dbname'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
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
