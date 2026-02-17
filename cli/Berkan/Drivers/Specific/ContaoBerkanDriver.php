<?php

namespace Berkan\Drivers\Specific;

use Berkan\Drivers\BerkanDriver;

class ContaoBerkanDriver extends BerkanDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath . '/system/initialize.php')
            || is_dir($sitePath . '/vendor/contao');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false
    {
        $publicDir = $this->getPublicDir($sitePath);

        if (file_exists($staticFilePath = $sitePath . $publicDir . $uri)
            && ! is_dir($staticFilePath)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        $publicDir = $this->getPublicDir($sitePath);

        $_SERVER['SCRIPT_FILENAME'] = $sitePath . $publicDir . '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['DOCUMENT_ROOT'] = $sitePath . $publicDir;

        return $sitePath . $publicDir . '/index.php';
    }

    /**
     * Determine the public directory for the Contao installation.
     */
    protected function getPublicDir(string $sitePath): string
    {
        if (file_exists($sitePath . '/public/index.php')) {
            return '/public';
        }

        return '/web';
    }
}
