<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Ext;

use Dubas\Console\Process\ProcessInterface;
use Dubas\Console\Util\PathUtil;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ExtInstallCommand extends Command
{
    public static string $messageOnConsoleStart = 'Installing additional extension(s)';

    public static string $messageOnConsoleSuccess = 'Extensions installed';

    protected static $defaultName = 'ext:install';

    protected static $defaultDescription = 'Install a single or multiple EspoCRM extensions';

    public function __construct(
        private PathUtil $pathUtil,
        private ProcessInterface $process,
        private SymfonyStyle $symfonyStyle
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $this->pathUtil->getWorkingDirectory($input);
        $instancePath = $this->pathUtil->getInstancePath($input);

        $isInstalled = false;

        if (is_file($workingDirectory)) {
            $isInstalled = $this->installExtension($workingDirectory, $instancePath);
        }

        if (is_dir($workingDirectory)) {
            $fileList = scandir($workingDirectory) ?: [];

            foreach ($fileList as $file) {
                if (!str_ends_with($file, '.zip')) {
                    continue;
                }

                $isInstalled = $this->installExtension($workingDirectory . '/' . $file, $instancePath);
            }
        }

        if (!$isInstalled) {
            $output->writeln(
                sprintf('<comment> %s</>', 'No additional extension(s) has been found')
            );
        }

        return Command::SUCCESS;
    }

    private function installExtension(string $file, string $workingDirectory): bool
    {
        $this->symfonyStyle->writeln('Installing ' . basename($file));

        $process = $this->process
            ->execute([
                'php', 'command.php', 'extension', '--file=' . $file,
            ])
            ->setWorkingDirectory($workingDirectory);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            $this->symfonyStyle->writeln(
                sprintf('<error>%s</>', $e->getMessage())
            );
            exit(1);
        }

        $this->symfonyStyle->writeln(
            sprintf('<info>%s</>', $process->getOutput())
        );

        return true;
    }
}
