<?php

declare(strict_types=1);

namespace Dubas\Console\Tool;

use stdClass;

class NpmTool
{
    public const PACKAGE_MANAGER_NPM = 'npm';

    public const PACKAGE_MANAGER_PNPM = 'pnpm';

    private string $cwd;

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
}
