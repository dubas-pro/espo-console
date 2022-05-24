<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Config;

use Dubas\Console\Espo\EspoManagerInterface;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ConfigMergeCommand extends Command
{
    public static string $messageOnConsoleStart = 'Merging configs';

    public static string $messageOnConsoleSuccess = 'Configs merged';

    protected static $defaultName = 'config:merge';

    protected static $defaultDescription = 'Merge configs';

    public function __construct(
        private PathUtil $pathUtil,
        private EspoManagerInterface $espoManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $this->pathUtil->getWorkingDirectory($input);
        $instancePath = $this->pathUtil->getInstancePath($input);

        $configFile = $workingDirectory . '/config.php';
        if (!file_exists($configFile)) {
            $output->writeln(
                sprintf('<comment>%s</>', 'Nothing to merge. Skipping.')
            );

            return Command::INVALID;
        }

        $override = include $configFile;

        $espoManager = $this->espoManager
            ->setWorkingDirectory($instancePath);

        $config = $espoManager->getConfig();
        $configWriter = $espoManager->createConfigWriter();

        foreach ($override as $key => $value) {
            if (is_array($value) && $config->has($key)) { /** @phpstan-ignore-line */
                $value = array_merge($value, $config->get($key)); /** @phpstan-ignore-line */
            }

            $configWriter->set($key, $value); /** @phpstan-ignore-line */
        }

        $configWriter->save(); /** @phpstan-ignore-line */

        return Command::SUCCESS;
    }
}
