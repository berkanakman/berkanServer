<?php

/**
 * Berkan - Apache-based PHP Development Environment
 *
 * This is the main request router script. Apache routes all requests
 * to this file, which then determines the appropriate site and driver.
 */

// Define constants
define('BERKAN_HOME_PATH', $_SERVER['HOME'] . '/.config/berkan');
define('BERKAN_SERVER_PATH', __DIR__);
define('BERKAN_STATIC_PREFIX', '/41c270e4-5535-4daa-b23e-c269e73c6c01');

// Load autoloader
require_once __DIR__ . '/vendor/autoload.php';

use Berkan\Server;
use Berkan\Drivers\BerkanDriver;

// Get the request URI and host
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$serverName = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

// Check for Berkan static file prefix
if (str_starts_with($uri, BERKAN_STATIC_PREFIX)) {
    $uri = substr($uri, strlen(BERKAN_STATIC_PREFIX));
}

// Resolve the site
[$sitePath, $siteName, $uri] = Server::resolve($uri, $serverName);

// If no site path was found, show 404
if (empty($sitePath) || ! is_dir($sitePath)) {
    Server::show404();
    return;
}

// Find the appropriate driver
$driver = BerkanDriver::assign($sitePath, $siteName, $uri);

if (! $driver) {
    Server::show404();
    return;
}

// Allow the driver to mutate the URI
$uri = $driver->mutateUri($uri);

// Check if the request is for a static file
$staticFilePath = $driver->isStaticFile($sitePath, $siteName, $uri);

if ($staticFilePath) {
    $driver->serveStaticFile($staticFilePath, $sitePath, $siteName, $uri);
    return;
}

// Get the front controller path
$frontControllerPath = $driver->frontControllerPath($sitePath, $siteName, $uri);

if (! file_exists($frontControllerPath)) {
    Server::show404();
    return;
}

// Set up the server environment
$_SERVER['SERVER_NAME'] = $serverName;
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = $uri;
$_SERVER['DOCUMENT_ROOT'] = $sitePath;
$_SERVER['SCRIPT_FILENAME'] = $frontControllerPath;

// Change to the site directory for relative path resolution
chdir(dirname($frontControllerPath));

// Include the front controller
require $frontControllerPath;
