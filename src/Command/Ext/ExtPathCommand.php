<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Ext;

use Dubas\Console\Tool\ExtensionTool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

final class ExtPathCommand extends Command
{
    protected static $defaultName = 'ext:path';

    protected static $defaultDescription = 'Return path to the backend directory';

    public function __construct(private ExtensionTool $extensionTool)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('client', null, InputOption::VALUE_NONE, 'Return path to the frontend directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $this->extensionTool->getApplicationDirectory();

        if ($input->getOption('client')) {
            $directory = $this->extensionTool->getClientDirectory();
        }

        $output->writeln($directory, Output::OUTPUT_RAW);

        return Command::SUCCESS;
    }
}
