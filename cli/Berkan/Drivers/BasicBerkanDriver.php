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
        return $this->validStaticFilePath($sitePath . $uri, $sitePath);
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        $realSitePath = realpath($sitePath);

        $candidates = [
            $sitePath . $uri,
            $sitePath . '/index.php',
            $sitePath . '/index.html',
        ];

        foreach ($candidates as $candidate) {
            $realCandidate = realpath($candidate);

            if ($realCandidate && strpos($realCandidate, $realSitePath) === 0 && ! is_dir($realCandidate)) {
                $_SERVER['SCRIPT_FILENAME'] = $realCandidate;
                $_SERVER['SCRIPT_NAME'] = str_replace($realSitePath, '', $realCandidate);
                $_SERVER['DOCUMENT_ROOT'] = $realSitePath;

                return $realCandidate;
            }
        }

        return $sitePath . '/index.php';
    }
}
