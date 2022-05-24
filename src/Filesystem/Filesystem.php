<?php

declare(strict_types=1);

namespace Dubas\Console\Filesystem;

use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class Filesystem extends SymfonyFilesystem implements FilesystemInterface
{
}
