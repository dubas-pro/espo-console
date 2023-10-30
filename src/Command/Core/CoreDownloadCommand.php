<?php

declare(strict_types=1);

namespace Dubas\Console\Command\Core;

use Dubas\Console\Filesystem\FilesystemInterface;
use Dubas\Console\Tool\CommandRunnerTool;
use Dubas\Console\Util\ConfigUtil;
use Dubas\Console\Util\PathUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use ZipArchive;

final class CoreDownloadCommand extends Command
{
    public static string $messageOnConsoleStart = 'Fetching EspoCRM repository';

    public static string $messageOnConsoleSuccess = 'EspoCRM package has been downloaded';

    protected static $defaultName = 'core:download';

    protected static $defaultDescription = 'Download core EspoCRM files';

    private string $temporaryDirectory;

    public function __construct(
        private ConfigUtil $configUtil,
        private SymfonyStyle $symfonyStyle,
        private PathUtil $pathUtil,
        private FilesystemInterface $filesystem,
        private Finder $finder,
        private CommandRunnerTool $commandRunnerTool
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $configData = $this->configUtil->getConfigData();
        $defaultDownloadUrl = $configData['espocrm']['repository'] ?? 'https://github.com/espocrm/espocrm.git';
        $defaultBranch = $configData['espocrm']['branch'] ?? 'stable';
        $defaultType = $configData['espocrm']['type'] ?? 'full';

        $this->addArgument('download-url', InputArgument::OPTIONAL, 'Package URL / Git repository', $defaultDownloadUrl);
        $this->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Branch or EspoCRM version', $defaultBranch);
        $this->addOption('type', 't', InputOption::VALUE_REQUIRED, trim(
            'Specify the package type to download from official repository
            Type <comment>full</comment> to download complete source files
            or <comment>release</comment> for pre-built package <info>(faster)</info>'
        ), $defaultType);
        $this->addOption('build', '', InputOption::VALUE_NONE, 'Build EspoCRM instance after download');
        $this->addOption('install', 'i', InputOption::VALUE_NONE, 'Install EspoCRM instance after download');
        $this->addOption('no-cache', '', InputOption::VALUE_NONE, 'Disables the use of the cache directory');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->temporaryDirectory = $this->pathUtil->getTemporaryPath();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (
            !$input->getOption('no-interaction')
            && !$this->symfonyStyle->confirm('This will remove any existing instance. Are you sure?', false)
        ) {
            $output->writeln(
                sprintf('<error>%s</>', 'Aborted')
            );

            return Command::INVALID;
        }

        if (0 !== $this->doExecute($input, $output)) {
            return Command::FAILURE;
        }

        return $this->afterExecute($input, $output);
    }

    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $instancePath = $this->pathUtil->getInstancePath($input);

        if ($this->filesystem->exists($instancePath)) {
            $this->filesystem->remove($instancePath);
        }

        $downloadType = 'Direct';

        if (str_starts_with($input->getArgument('download-url'), 'https://github.com')) {
            $downloadType = 'Github';
        }

        if (str_starts_with($input->getArgument('download-url'), 'https://github.com/espocrm/espocrm')) {
            $downloadType = 'OfficialRepository';
        }

        $methodName = 'processDownloadFor' . $downloadType;

        if (method_exists($this, $methodName)) {
            return $this->$methodName($input, $output);
        }

        return Command::INVALID;
    }

    protected function afterExecute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->filesystem->exists($this->temporaryDirectory . '/espocrm')) {
            $this->filesystem->remove($this->temporaryDirectory . '/espocrm');
        }

        if ($input->getOption('install')) {
            return $this->runCoreInstallCommand($input, $output);
        }

        return Command::SUCCESS;
    }

    protected function processDownloadForOfficialRepository(InputInterface $input, OutputInterface $output): int
    {
        $instancePath = $this->pathUtil->getInstancePath($input);
        $cmd = $this->commandRunnerTool->setApplication($this->getApplication());
        $branch = $input->getOption('branch');
        $type = mb_strtolower($input->getOption('type'));
        $downloadUrl = $input->getArgument('download-url');
        $noCache = $input->getOption('no-cache');

        // Do not cache files from master and stable branch.
        if (in_array($branch, ['master', 'stable'], true)) {
            $noCache = true;
        }

        $output->writeln('Downloading EspoCRM <info>' . $branch . '</info> package from Github');

        if (str_ends_with($downloadUrl, '.git')) {
            $downloadUrl = substr($downloadUrl, 0, -4);
        }

        $downloadUrl = rtrim($downloadUrl, '/');

        switch ($type) {
            case 'full':
                $downloadUrl .= '/archive/' . $branch . '.zip';
                break;

            case 'release':
                $downloadUrl .= '/releases/download/' . $branch . '/EspoCRM-' . $branch . '.zip';
                break;
        }

        $localPackageFilePath = $this->temporaryDirectory . '/' . $type . '/EspoCRM-' . $branch . '.zip';

        if (
            !$this->downloadFile($downloadUrl, $localPackageFilePath, $noCache) ||
            !$this->filesystem->exists($localPackageFilePath)
        ) {
            $output->writeln(
                sprintf('<error>%s</>', 'EspoCRM package could not be downloaded')
            );

            return Command::FAILURE;
        }

        $extractToPath = $this->temporaryDirectory . '/espocrm';
        if (
            !$this->unzip($localPackageFilePath, $extractToPath) ||
            !$this->filesystem->exists($extractToPath)
        ) {
            $output->writeln(
                sprintf('<error>%s</>', 'EspoCRM package could not be unzipped')
            );

            return Command::FAILURE;
        }

        $espocrmFinder = $this->finder
            ->directories()
            ->in($extractToPath)
            ->depth(0);

        if ($espocrmFinder->hasResults() !== true) {
            $output->writeln(
                sprintf('<error>%s</>', 'Something went wrong while downloading EspoCRM')
            );

            return Command::FAILURE;
        }

        $espocrmPackage = null;
        foreach ($espocrmFinder as $espocrm) {
            if ($this->filesystem->exists($espocrm->getRealPath() . '/bootstrap.php')) {
                $espocrmPackage = $espocrm->getRealPath();
                break;
            }
        }

        if ($espocrmPackage) {
            $this->filesystem->mirror($espocrmPackage, $instancePath);
        }

        if ($input->getOption('build') && $this->filesystem->exists($espocrmPackage . '/Gruntfile.js')) {
            if (0 !== $cmd->runCommand('core:build --build=dev --instance=' . $instancePath, $output)) {
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    private function downloadFile(string $url, string $localFilePath, bool $noCache = false): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200) {
            return false;
        }

        if (!$this->filesystem->exists(dirname($localFilePath))) {
            $this->filesystem->mkdir(dirname($localFilePath));
        }

        if ($this->filesystem->exists($localFilePath) && $noCache) {
            $this->filesystem->remove($localFilePath);
        }

        if (!$this->filesystem->exists($localFilePath)) {
            file_put_contents($localFilePath, fopen($url, 'r'));
        }

        return true;
    }

    private function unzip(string $file, string $extractToPath): bool
    {
        if ($this->filesystem->exists($extractToPath)) {
            $this->filesystem->remove($extractToPath);
        }

        $zip = new ZipArchive();

        $res = $zip->open($file);

        if ($res === true) {
            $zip->extractTo($extractToPath . '/');
            $zip->close();

            return true;
        }

        return false;
    }

    private function runCoreInstallCommand(InputInterface $input, OutputInterface $output): int
    {
        $command = 'core:install';

        if ($input->getOption('type') !== 'release') {
            $command .= ' --dev-mode';
        }

        return $this->commandRunnerTool
            ->setApplication($this->getApplication())
            ->runCommand($command, $output);
    }
}
