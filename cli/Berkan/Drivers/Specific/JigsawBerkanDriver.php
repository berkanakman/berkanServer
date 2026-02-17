<?php

namespace Berkan\Drivers\Specific;

use Berkan\Drivers\BerkanDriver;

class JigsawBerkanDriver extends BerkanDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return is_dir($sitePath . '/build_local');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false
    {
        if (file_exists($staticFilePath = $sitePath . '/build_local' . $uri)
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
        $_SERVER['SCRIPT_FILENAME'] = $sitePath . '/build_local/index.html';
        $_SERVER['SCRIPT_NAME'] = '/index.html';
        $_SERVER['DOCUMENT_ROOT'] = $sitePath . '/build_local';

        return $sitePath . '/build_local/index.html';
    }
}
