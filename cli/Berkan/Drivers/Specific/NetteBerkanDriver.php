<?php

namespace Berkan\Drivers\Specific;

use Berkan\Drivers\BerkanDriver;

class NetteBerkanDriver extends BerkanDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath . '/app/bootstrap.php')
            && file_exists($sitePath . '/www/index.php');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)
    {
        return $this->validStaticFilePath($sitePath . '/www' . $uri, $sitePath);
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        $_SERVER['SCRIPT_FILENAME'] = $sitePath . '/www/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['DOCUMENT_ROOT'] = $sitePath . '/www';

        return $sitePath . '/www/index.php';
    }
}
