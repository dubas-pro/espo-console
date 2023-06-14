<?php

declare(strict_types=1);

namespace Dubas\Console;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;

final class Application extends SymfonyApplication
{
    protected ContainerInterface $container;

    /**
     * @param Command[]|Traversable $commands
     */
    public function __construct(Traversable $commands)
    {
        $this->addCommands(iterator_to_array($commands));

        parent::__construct(__NAMESPACE__, '0.0.11');
    }

    /**
     * @license Copyright (c) Matthieu Napoli
     *
     * Helper to run a sub-command from a command.
     *
     * @param string $command Command that should be run.
     * @param OutputInterface|null $output The output to use. If not provided, the output will be silenced.
     *
     * @return int 0 if everything went fine, or an error code
     */
    public function runCommand(string $command, OutputInterface $output = null): int
    {
        $input = new StringInput($command);

        if (!$commandName = $this->getCommandName($input)) {
            return Command::FAILURE;
        }

        $command = $this->find($commandName);

        if ($output) {
            $reflectionClass = new \ReflectionClass($command);

            if ($reflectionClass->hasProperty('messageOnConsoleStart')) {
                $output->writeln(
                    $reflectionClass->getStaticPropertyValue('messageOnConsoleStart')
                );
            }
        }

        return $command->run($input, $output ?: new NullOutput());
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $defaultInputDefinition = parent::getDefaultInputDefinition();

        $this->addCustomOptions($defaultInputDefinition);

        return $defaultInputDefinition;
    }

    private function addCustomOptions(InputDefinition $inputDefinition): void
    {
        $inputDefinition->addOption(
            new InputOption('--working-dir', 'd', InputOption::VALUE_REQUIRED, 'If specified, use the given directory as working directory', WORKING_DIRECTORY)
        );

        $inputDefinition->addOption(
            new InputOption('--instance', 'I', InputOption::VALUE_REQUIRED, 'Path to EspoCRM instance', 'site')
        );
    }
}
