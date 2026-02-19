<?php

namespace Berkan\Drivers;

class BasicBerkanDriver extends BerkanDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return true;
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)
    {
        if (file_exists($staticFilePath = $sitePath . $uri) && ! is_dir($staticFilePath)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        $candidates = [
            $sitePath . $uri,
            $sitePath . '/index.php',
            $sitePath . '/index.html',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && ! is_dir($candidate)) {
                $_SERVER['SCRIPT_FILENAME'] = $candidate;
                $_SERVER['SCRIPT_NAME'] = str_replace($sitePath, '', $candidate);
                $_SERVER['DOCUMENT_ROOT'] = $sitePath;

                return $candidate;
            }
        }

        return $sitePath . '/index.php';
    }
}
