<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Ext;

use Dubas\Console\Filesystem\FilesystemInterface;
use Dubas\Console\Tool\CommandRunnerTool;
use Dubas\Console\Tool\ExtensionTool;
use Dubas\Console\Tool\NpmTool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use ZipArchive;

final class ExtBuildCommand extends Command
{
    public static string $messageOnConsoleStart = 'Building extension package';

    public static string $messageOnConsoleSuccess = 'Package has been built';

    protected static $defaultName = 'ext:build';

    protected static $defaultDescription = 'Build an installable extension package';

    /**
     * @var array<string>
     */
    private array $filesToIgnore = [
        'composer.json',
        'composer.lock',
        'composer.phar',
        'package.json',
        'package.json',
        'package-lock.json',
        'pnpm-lock.yaml',
        'Gruntfile.js',
        'node_modules',
    ];

    public function __construct(
        private FilesystemInterface $filesystem,
        private ExtensionTool $extensionTool,
        private NpmTool $npmTool,
        private CommandRunnerTool $commandRunnerTool,
        private Finder $finder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dev', '', InputOption::VALUE_NONE, 'Skip build process and installs Composer and Node.js dependencies to src directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cmd = $this->commandRunnerTool->setApplication($this->getApplication());

        $extensionTool = $this->extensionTool;
        $npmTool = $this->npmTool;

        if ($input->getOption('dev')) {
            $cmd->runCommand('ext:composer-install --working-dir=' . $extensionTool->getApplicationDirectory());
            $cmd->runCommand('ext:npm-install --working-dir=' . $extensionTool->getClientDirectory());

            return Command::SUCCESS;
        }

        $extensionData = $extensionTool->getData();

        $manifest = [
            'name' => $extensionData->name,
            'description' => $extensionData->description,
            'author' => $extensionData->author,
            'php' => $extensionData->php,
            'acceptableVersions' => $extensionData->acceptableVersions,
            'checkVersionUrl' => $extensionData->checkVersionUrl,
            'version' => $npmTool->getPackageVersion(),
            'skipBackup' => true,
            'releaseDate' => date('Y-m-d'),
        ];

        $packageFileName = $extensionTool->getModuleNameHyphen() . '-' . $npmTool->getPackageVersion() . '.zip';

        $buildPath = WORKING_DIRECTORY . '/build';
        $tempPath = $buildPath . '/tmp';

        if (!$this->filesystem->exists($buildPath)) {
            $this->filesystem->mkdir($buildPath);
        }

        if ($this->filesystem->exists($tempPath)) {
            $this->filesystem->remove($tempPath);
        }

        if ($this->filesystem->exists($buildPath . '/' . $packageFileName)) {
            $this->filesystem->remove($buildPath . '/' . $packageFileName);
        }

        $this->filesystem->mkdir($tempPath);

        $this->filesystem->mirror(WORKING_DIRECTORY . '/src', $tempPath);

        $cmd->runCommand('ext:composer-install --no-dev --working-dir=' . $extensionTool->getTemporaryApplicationDirectory());
        $cmd->runCommand('ext:npm-install --working-dir=' . $extensionTool->getTemporaryClientDirectory());

        $clean = $this->getNewFinderInstance()
            ->files()
            ->name($this->filesToIgnore)
            ->in($tempPath);

        foreach ($clean as $file) {
            $this->filesystem->remove($file->getRealPath());
        }

        file_put_contents($tempPath . '/manifest.json', print_r(
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            true
        ));

        if (!class_exists(ZipArchive::class)) {
            $output->write(
                sprintf('<error>%s</>', 'class ExtZipArchive is not installed')
            );

            return Command::FAILURE;
        }

        $tempFileList = $this->getNewFinderInstance()
            ->files()
            ->in($tempPath);

        $zip = new ZipArchive();
        if (true === $zip->open($buildPath . '/' . $packageFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach ($tempFileList as $tempFile) {
                $zip->addFile($tempFile->getRealPath(), $tempFile->getRelativePathname());
            }

            $zip->close();
        }

        $this->filesystem->remove($tempPath);

        return Command::SUCCESS;
    }

    private function getNewFinderInstance(): Finder
    {
        return $this->finder::create();
    }
}
