<?php

declare(strict_types=1);

namespace Dubas\Console\EventListener;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleEventListener
{
    public function __construct(
        private SymfonyStyle $symfonyStyle
    ) {
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $this->displayMessageOnConsoleStart($event->getCommand());
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command) {
            return;
        }

        $reflectionClass = new \ReflectionClass($command);

        if (
            $reflectionClass->hasProperty('messageOnConsoleSuccess')
            && 0 === $event->getExitCode()
        ) {
            $this->symfonyStyle->newLine();
            $this->symfonyStyle->writeln(
                sprintf('<info>%s</>', $reflectionClass->getStaticPropertyValue('messageOnConsoleSuccess'))
            );
        }
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
    }

    private function displayMessageOnConsoleStart(Command $command = null): void
    {
        if (null === $command) {
            return;
        }

        $reflectionClass = new \ReflectionClass($command);

        if ($reflectionClass->hasProperty('messageOnConsoleStart')) {
            $this->symfonyStyle->title(
                $reflectionClass->getStaticPropertyValue('messageOnConsoleStart')
            );
        }
    }
}
