<?php

namespace Berkan\Drivers\Specific;

use Berkan\Drivers\BerkanDriver;

class CraftBerkanDriver extends BerkanDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath . '/craft');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)
    {
        if (file_exists($staticFilePath = $sitePath . '/web' . $uri)
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
        $_SERVER['SCRIPT_FILENAME'] = $sitePath . '/web/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['DOCUMENT_ROOT'] = $sitePath . '/web';

        return $sitePath . '/web/index.php';
    }
}
