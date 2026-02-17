<?php

/**
 * Berkan CLI Entry Point
 *
 * Load the Composer autoloader and bootstrap the application.
 */

use Illuminate\Container\Container;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Define the Berkan constants.
 */
define('BERKAN_LOOPBACK', '127.0.0.1');
define('BREW_PREFIX', (PHP_OS_FAMILY === 'Darwin' && PHP_INT_SIZE === 8 && str_contains(php_uname('m'), 'arm'))
    ? '/opt/homebrew'
    : '/usr/local');
define('BERKAN_HOME_PATH', $_SERVER['HOME'] . '/.config/berkan');

/**
 * Load the Composer autoloader.
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Create the application container.
 */
$container = Container::getInstance();

/**
 * Register core singletons.
 */
$container->singleton(ConsoleOutput::class, function () {
    return new ConsoleOutput;
});

$container->singleton(\Berkan\CommandLine::class, function () {
    return new \Berkan\CommandLine;
});

$container->singleton(\Berkan\Filesystem::class, function () {
    return new \Berkan\Filesystem;
});

$container->singleton(\Berkan\Composer::class, function ($app) {
    return new \Berkan\Composer(
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class)
    );
});

$container->singleton(\Berkan\Configuration::class, function ($app) {
    return new \Berkan\Configuration(
        $app->make(\Berkan\Filesystem::class)
    );
});

$container->singleton(\Berkan\Brew::class, function ($app) {
    return new \Berkan\Brew(
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class)
    );
});

$container->singleton(\Berkan\Site::class, function ($app) {
    return new \Berkan\Site(
        $app->make(\Berkan\Configuration::class),
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class)
    );
});

$container->singleton(\Berkan\Contracts\WebServer::class, function ($app) {
    $config = $app->make(\Berkan\Configuration::class)->read();
    $webServerType = $config['web_server'] ?? 'apache';

    if ($webServerType === 'nginx') {
        $server = new \Berkan\Nginx(
            $app->make(\Berkan\Brew::class),
            $app->make(\Berkan\CommandLine::class),
            $app->make(\Berkan\Filesystem::class),
            $app->make(\Berkan\Configuration::class),
            $app->make(\Berkan\Site::class)
        );
    } else {
        $server = new \Berkan\Apache(
            $app->make(\Berkan\Brew::class),
            $app->make(\Berkan\CommandLine::class),
            $app->make(\Berkan\Filesystem::class),
            $app->make(\Berkan\Configuration::class),
            $app->make(\Berkan\Site::class)
        );
    }

    // Set WebServer reference on Site for bidirectional access
    $app->make(\Berkan\Site::class)->setWebServer($server);

    return $server;
});

$container->singleton(\Berkan\DnsMasq::class, function ($app) {
    return new \Berkan\DnsMasq(
        $app->make(\Berkan\Brew::class),
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class),
        $app->make(\Berkan\Configuration::class)
    );
});

$container->singleton(\Berkan\PhpFpm::class, function ($app) {
    return new \Berkan\PhpFpm(
        $app->make(\Berkan\Brew::class),
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class),
        $app->make(\Berkan\Configuration::class)
    );
});

$container->singleton(\Berkan\Database::class, function ($app) {
    return new \Berkan\Database(
        $app->make(\Berkan\Brew::class),
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class),
        $app->make(\Berkan\Configuration::class)
    );
});

$container->singleton(\Berkan\Status::class, function ($app) {
    return new \Berkan\Status(
        $app->make(\Berkan\Contracts\WebServer::class),
        $app->make(\Berkan\PhpFpm::class),
        $app->make(\Berkan\DnsMasq::class),
        $app->make(\Berkan\Configuration::class)
    );
});

$container->singleton(\Berkan\Diagnose::class, function ($app) {
    return new \Berkan\Diagnose(
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class),
        $app->make(\Berkan\Configuration::class),
        $app->make(\Berkan\Brew::class),
        $app->make(\Berkan\Contracts\WebServer::class),
        $app->make(\Berkan\PhpFpm::class),
        $app->make(\Berkan\DnsMasq::class)
    );
});

$container->singleton(\Berkan\Berkan::class, function ($app) {
    return new \Berkan\Berkan(
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class),
        $app->make(\Berkan\Configuration::class)
    );
});

$container->singleton(\Berkan\Ngrok::class, function ($app) {
    return new \Berkan\Ngrok(
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class)
    );
});

$container->singleton(\Berkan\Expose::class, function ($app) {
    return new \Berkan\Expose(
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class)
    );
});

$container->singleton(\Berkan\Cloudflared::class, function ($app) {
    return new \Berkan\Cloudflared(
        $app->make(\Berkan\CommandLine::class),
        $app->make(\Berkan\Filesystem::class)
    );
});

/**
 * Load the application commands and run.
 */
$app = require __DIR__ . '/app.php';

$app->run();
