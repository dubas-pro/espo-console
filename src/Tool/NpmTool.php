<?php

declare(strict_types=1);

namespace Dubas\Console\Tool;

use Dubas\Console\Process\ProcessInterface;
use stdClass;
use Symfony\Component\Process\Exception\ProcessFailedException;

class NpmTool
{
    public const PACKAGE_MANAGER_NPM = 'npm';

    public const PACKAGE_MANAGER_PNPM = 'pnpm';

    private string $cwd;

    public function __construct(private ProcessInterface $process)
    {
    }

    public function getWorkingDirectory(): string
    {
        return $this->cwd ?? WORKING_DIRECTORY;
    }

    public function setWorkingDirectory(string $cwd): self
    {
        $obj = clone $this;
        $obj->cwd = $cwd;

        return $obj;
    }

    public function getPackageJsonData(): stdClass
    {
        $packageJsonPath = $this->getWorkingDirectory() . '/package.json';

        if (!file_exists($packageJsonPath)) {
            return (object) [];
        }

        $packageJson = file_get_contents($packageJsonPath) ?: '';

        return json_decode($packageJson, false, 512, JSON_THROW_ON_ERROR);
    }

    public function getPackageVersion(): string
    {
        return $this->getPackageJsonData()->version ?? '0.0.1';
    }

    public function getPackageManagerType(?string $packageManager = null): string
    {
        if ($packageManager) {
            return mb_strtolower($packageManager);
        }

        $extensionPackageManager = $this->getPackageJsonData()->packageManager ?? '';

        $packageManager = explode('@', $extensionPackageManager)[0] ?? null;
        $packageManager = $packageManager ?: self::PACKAGE_MANAGER_NPM;

        return $packageManager;
    }

    public function pnpmVersion(): string
    {
        $process = $this->process
            ->execute(['pnpm', '--version']);

        try {
            $process->mustRun();
        } catch (ProcessFailedException) {
        }

        return $process->getOutput();
    }

    public function pnpmIsInstalled(): bool
    {
        if ($this->pnpmVersion()) {
            return true;
        }

        try {
            $this->process
                ->execute(['npm', 'install', '--global', 'pnpm', '--no-audit', '--no-fund'])
                ->disableOutput()
                ->mustRun();
        } catch (ProcessFailedException) {
        }

        return '' !== $this->pnpmVersion();
    }
}
