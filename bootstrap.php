<?php

declare(strict_types=1);

define('WORKING_DIRECTORY', getcwd());

/**
 * Load correct autoloader depending on install location.
 */
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../autoload.php')) {
    require_once __DIR__ . '/../autoload.php';
} else {
    require_once __DIR__ . '/../../autoload.php';
}

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

$container = new ContainerBuilder();
$container->addCompilerPass(new AddConsoleCommandPass());
$container->addCompilerPass(new RegisterListenersPass());

$loader = new YamlFileLoader($container, new FileLocator(__DIR__));
$loader->load(__DIR__ . '/config/services.yaml');

$container->compile();

return $container;
