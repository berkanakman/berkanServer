<?php

namespace Berkan\Drivers\Specific;

use Berkan\Drivers\BerkanDriver;

class StatamicBerkanDriver extends BerkanDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        if (! file_exists($sitePath . '/artisan') && ! file_exists($sitePath . '/bootstrap/app.php')) {
            return false;
        }

        $composerFile = $sitePath . '/composer.json';

        if (! file_exists($composerFile)) {
            return false;
        }

        $composer = json_decode(file_get_contents($composerFile), true);

        return isset($composer['require']['statamic/cms'])
            || isset($composer['require-dev']['statamic/cms']);
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)
    {
        return $this->validStaticFilePath($sitePath . '/public' . $uri, $sitePath);
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        $_SERVER['SCRIPT_FILENAME'] = $sitePath . '/public/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['DOCUMENT_ROOT'] = $sitePath . '/public';

        return $sitePath . '/public/index.php';
    }
}
