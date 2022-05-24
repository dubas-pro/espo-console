<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Import;

use Dubas\Console\Espo\EspoManagerInterface;
use Dubas\Console\Tool\CommandRunnerTool;
use Dubas\Console\Util\ConfigUtil;
use Dubas\Console\Util\PathUtil;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportTestDataCommand extends Command
{
    public static string $messageOnConsoleStart = 'Importing test data';

    public static string $messageOnConsoleSuccess = 'Import finished';

    protected static $defaultName = 'import:test-data';

    protected static $defaultDescription = 'Import test data';

    public function __construct(
        private PathUtil $pathUtil,
        private ConfigUtil $configUtil,
        private EspoManagerInterface $espoManager,
        private CommandRunnerTool $commandRunnerTool
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('skip-default', '', InputOption::VALUE_NONE, 'If set, skips importing default fixtures');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $this->pathUtil->getWorkingDirectory($input);
        $instancePath = $this->pathUtil->getInstancePath($input);
        $configData = $this->configUtil->getConfigData();

        $fixtureList = [];
        if (!$input->getOption('skip-default')) {
            $output->writeln('Included: ' . dirname(__DIR__, 3) . '/data/fixtures/init.php');

            $fixtureList[] = include dirname(__DIR__, 3) . '/data/fixtures/init.php';
        }

        $customFixturesList = $configData['customFixtures'] ?? [];
        if (!is_array($customFixturesList)) {
            $customFixturesList = (array) $customFixturesList;
        }

        foreach ($customFixturesList as $customFixtures) {
            if (!file_exists($customFixtures)) {
                $customFixtures = $workingDirectory . '/' . $customFixtures;
            }

            if (file_exists($customFixtures)) {
                $output->writeln('Included: ' . $customFixtures);

                $fixtureList[] = include $customFixtures;
            }
        }

        if (empty($fixtureList)) {
            $output->writeln(
                sprintf('<comment>%s</>', 'Nothing to import. Skipping.')
            );

            return Command::INVALID;
        }

        foreach ($fixtureList as $fixtures) {
            foreach ($fixtures as $type => $data) {
                if ('entities' === $type) {
                    try {
                        $this->espoManager
                            ->setWorkingDirectory($instancePath)
                            ->importEntities($data);
                    } catch (Exception $e) {
                        $output->writeln(
                            sprintf('<error>%s</>', $e->getMessage())
                        );

                        return Command::FAILURE;
                    }
                }
            }
        }

        $this->commandRunnerTool
            ->setApplication($this->getApplication())
            ->runCommand('admin:clear-cache', $output);

        return Command::SUCCESS;
    }
}
