<?php

declare(strict_types=1);

namespace Dubas\Console\Util;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Path;

class PathUtil
{
    /**
     * Returns an absolute path to a working directory.
     */
    public function getWorkingDirectory(InputInterface $input): string
    {
        return $this->getAbsolutePath(
            $input->getOption('working-dir')
        );
    }

    /**
     * Returns an absolute path to EspoCRM instance.
     */
    public function getInstancePath(InputInterface $input): string
    {
        return $this->getAbsolutePath(
            $input->getOption('instance'),
            $this->getWorkingDirectory($input)
        );
    }

    /**
     * Returns an absolute path to the temporary directory.
     */
    public function getTemporaryPath(string $baseName = 'espo-console'): string
    {
        $temporaryDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/' . $baseName;

        if (file_exists($temporaryDirectory) !== true) {
            mkdir($temporaryDirectory, 0777, true);
        }

        return $temporaryDirectory;
    }

    /**
     * Ensures the given path is an absolute path.
     */
    public function getAbsolutePath(string $path, ?string $basePath = null): string
    {
        $basePath = $basePath ?: WORKING_DIRECTORY;

        $path = Path::canonicalize($path);

        if (Path::isRelative($path)) {
            $path = Path::makeAbsolute($path, $basePath);
        }

        return $path;
    }
}
