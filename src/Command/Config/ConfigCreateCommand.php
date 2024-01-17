<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Config;

use Dubas\Console\Util\ConfigUtil;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ConfigCreateCommand extends Command
{
    public static string $messageOnConsoleStart = 'Creating config';

    public static string $messageOnConsoleSuccess = 'Config created';

    protected static $defaultName = 'config:create';

    protected static $defaultDescription = 'Create config';

    public function __construct(
        private PathUtil $pathUtil,
        private ConfigUtil $configUtil
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', '', InputOption::VALUE_NONE, 'Overwrite existing file, if present')
            ->addOption('dev-mode', '', InputOption::VALUE_NONE, 'Toggle developer mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instancePath = $this->pathUtil->getInstancePath($input);
        $configData = $this->configUtil->getConfigData();

        if (
            !$input->getOption('force') &&
            file_exists($instancePath . '/data/config.php')
        ) {
            $output->writeln(
                sprintf('<comment>%s</>', 'Config already exists. Skipping.')
            );

            return Command::SUCCESS;
        }

        $configFile = [
            'database' => [
                'driver' => $configData['database']['driver'],
                'host' => $configData['database']['host'],
                'port' => $configData['database']['port'],
                'charset' => $configData['database']['charset'],
                'dbname' => $configData['database']['dbname'],
                'user' => $configData['database']['user'],
                'password' => $configData['database']['password'],
            ],
            'isDeveloperMode' => $input->getOption('dev-mode'),
            'useCache' => true,
        ];

        file_put_contents($instancePath . '/data/config.php', '<?php return ' . var_export($configFile, true) . ';');

        return Command::SUCCESS;
    }
}
