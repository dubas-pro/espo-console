<?php

declare(strict_types=1);

namespace Dubas\Console\Filesystem;

use Traversable;

interface FilesystemInterface
{
    /**
     * @param string|iterable<string> $dirs
     * @return void
     */
    public function mkdir(string|iterable $dirs, int $mode = 0777);

    /**
     * @param string|iterable<string> $files
     */
    public function exists(string|iterable $files): bool;

    /**
     * @param string|iterable<string> $files
     * @return void
     */
    public function remove(string|iterable $files);

    /**
     * @param Traversable<mixed>|null $iterator
     * @param array<string> $options
     * @return mixed
     */
    public function mirror(string $originDir, string $targetDir, Traversable $iterator = null, array $options = []);
}
