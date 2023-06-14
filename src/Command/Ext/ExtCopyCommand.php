<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Ext;

use Dubas\Console\Filesystem\FilesystemInterface;
use Dubas\Console\Tool\ExtensionTool;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

final class ExtCopyCommand extends Command
{
    public static string $messageOnConsoleStart = 'Copying extension files to EspoCRM instance';

    public static string $messageOnConsoleSuccess = 'Extension files copied';

    protected static $defaultName = 'ext:copy';

    protected static $defaultDescription = 'Copy extension files to EspoCRM instance';

    public function __construct(
        private PathUtil $pathUtil,
        private ExtensionTool $extensionTool,
        private FilesystemInterface $filesystem,
        private Finder $finder
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $this->pathUtil->getWorkingDirectory($input);
        $instancePath = $this->pathUtil->getInstancePath($input);

        $moduleFinder = $this->finder
            ->directories()
            ->name([
                $this->extensionTool->getModuleName(),
                $this->extensionTool->getModuleNameHyphen(),
            ])
            ->in($instancePath)
            ->exclude(['data', 'vendor'])
            ->sortByName();

        foreach ($moduleFinder as $module) {
            $path = $module->getRealPath();

            if ($path === false) {
                continue;
            }

            $output->writeln('Removing ' . $workingDirectory . '/' . $module->getRelativePathname());
            $this->filesystem->remove($path);
        }

        $this->filesystem->mirror($workingDirectory . '/src/files', $instancePath);
        $this->filesystem->mirror($workingDirectory . '/tests', $instancePath . '/tests');

        return Command::SUCCESS;
    }
}
