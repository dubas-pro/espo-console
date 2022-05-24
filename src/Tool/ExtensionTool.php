<?php

declare(strict_types=1);

namespace Dubas\Console\Tool;

use Jawira\CaseConverter\CaseConverterInterface;
use stdClass;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class ExtensionTool
{
    private const TMP_DIR = 'build/tmp';

    public function __construct(
        private Finder $finder,
        private CaseConverterInterface $caseConverter
    ) {
    }

    public function getData(): stdClass
    {
        $packageJsonPath = WORKING_DIRECTORY . '/extension.json';

        if (!file_exists($packageJsonPath)) {
            return (object) [];
        }

        $packageJson = file_get_contents($packageJsonPath) ?: '';

        return json_decode(
            $packageJson,
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function getModuleName(): string
    {
        return $this->getData()->module ?? '';
    }

    public function getModuleNameHyphen(): string
    {
        $moduleName = $this->getModuleName();

        return $this->caseConverter->convert($moduleName)->toKebab();
    }

    public function getApplicationDirectory(): string
    {
        return $this->findDirectory(
            $this->getModuleName()
        );
    }

    public function getClientDirectory(): string
    {
        return $this->findDirectory(
            $this->getModuleNameHyphen()
        );
    }

    public function getTemporaryApplicationDirectory(): string
    {
        return $this->findDirectory(
            $this->getModuleName(),
            self::TMP_DIR
        );
    }

    public function getTemporaryClientDirectory(): string
    {
        return $this->findDirectory(
            $this->getModuleNameHyphen(),
            self::TMP_DIR
        );
    }

    private function findDirectory(string $name, ?string $in = null): string
    {
        if (null === $in) {
            $in = 'src/files';
        }

        $directoryRealPath = '';

        $directoryFinder = [];

        try {
            $directoryFinder = $this->finder
                ->directories()
                ->name($name)
                ->in(WORKING_DIRECTORY . '/' . $in);
        } catch (DirectoryNotFoundException) {
        }

        if (empty($directoryFinder)) {
            return $directoryRealPath;
        }

        foreach ($directoryFinder as $directory) {
            $directoryRealPath = $directory->getRealPath();
            break;
        }

        return $directoryRealPath;
    }
}
