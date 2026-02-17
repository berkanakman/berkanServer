<?php

namespace Berkan;

use Berkan\Drivers\BerkanDriver;

class Server
{
    /**
     * Determine the site and URI from the request.
     */
    public static function resolve(string $uri, string $serverName): array
    {
        $siteName = static::extractSiteName($serverName);
        $sitePath = static::sitePath($siteName);

        return [$sitePath, $siteName, $uri];
    }

    /**
     * Extract the site name from the server name.
     */
    public static function extractSiteName(string $serverName): string
    {
        $config = static::loadConfig();
        $tld = $config['tld'] ?? 'test';

        // Remove the TLD from the host
        $siteName = str_replace('.' . $tld, '', $serverName);

        // Remove port if present
        $siteName = explode(':', $siteName)[0];

        return $siteName;
    }

    /**
     * Get the path for the given site name.
     */
    public static function sitePath(string $siteName): string
    {
        $config = static::loadConfig();
        $homePath = $_SERVER['HOME'] . Configuration::BERKAN_HOME_PATH;

        // Check linked sites first
        $linkPath = $homePath . '/Sites/' . $siteName;

        if (file_exists($linkPath)) {
            if (is_link($linkPath)) {
                return readlink($linkPath);
            }

            return $linkPath;
        }

        // Check parked paths
        $paths = $config['paths'] ?? [];

        foreach ($paths as $path) {
            $sitePath = $path . '/' . $siteName;

            if (is_dir($sitePath)) {
                return $sitePath;
            }
        }

        return '';
    }

    /**
     * Load the Berkan configuration.
     */
    public static function loadConfig(): array
    {
        $configPath = $_SERVER['HOME'] . Configuration::BERKAN_HOME_PATH . '/config.json';

        if (! file_exists($configPath)) {
            return ['tld' => 'test', 'loopback' => '127.0.0.1', 'paths' => []];
        }

        return json_decode(file_get_contents($configPath), true) ?: [];
    }

    /**
     * Serve the request.
     */
    public static function serverPath(): string
    {
        return realpath(__DIR__ . '/../../server.php');
    }

    /**
     * Show the 404 page.
     */
    public static function show404(): void
    {
        http_response_code(404);

        $templatePath = __DIR__ . '/../templates/404.html';

        if (file_exists($templatePath)) {
            echo file_get_contents($templatePath);
        } else {
            echo '<h1>404 - Site Not Found</h1>';
            echo '<p>The requested site was not found on this Berkan server.</p>';
        }
    }

    /**
     * Determine the appropriate driver for the request.
     */
    public static function findDriver(string $sitePath, string $siteName, string $uri): ?BerkanDriver
    {
        return BerkanDriver::assign($sitePath, $siteName, $uri);
    }
}
