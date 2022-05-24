<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Ext;

use Dubas\Console\Espo\EspoManagerInterface;
use Dubas\Console\Util\PathUtil;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ExtRunScriptCommand extends Command
{
    protected static $defaultName = 'ext:run-script';

    protected static $defaultDescription = 'Run script file';

    public function __construct(
        private PathUtil $pathUtil,
        private EspoManagerInterface $espoManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('script-name', 's', InputOption::VALUE_REQUIRED, 'Name of the script e.g. AfterInstall')
            ->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $this->pathUtil->getWorkingDirectory($input);
        $instancePath = $this->pathUtil->getInstancePath($input);

        $scriptName = $input->getOption('script-name');
        $scriptFile = $workingDirectory . '/src/scripts/' . $scriptName . '.php';

        if (!file_exists($scriptFile)) {
            $output->write(
                sprintf("<error>File '%s' does not exist.</>", $scriptFile)
            );

            return Command::INVALID;
        }

        include $scriptFile;

        $espoContainer = $this->espoManager
            ->setWorkingDirectory($instancePath)
            ->getContainer();

        try {
            $script = new $scriptName();
            /** @phpstan-ignore-next-line */
            $script->run($espoContainer);
        } catch (Exception $e) {
            $output->write(
                sprintf('<error>%s</>', $e->getMessage())
            );

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
