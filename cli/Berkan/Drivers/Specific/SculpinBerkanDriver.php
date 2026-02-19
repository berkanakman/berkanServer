<?php

namespace Berkan\Drivers\Specific;

use Berkan\Drivers\BerkanDriver;

class SculpinBerkanDriver extends BerkanDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return is_dir($sitePath . '/output_dev') || is_dir($sitePath . '/output_prod');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)
    {
        $outputDir = $this->getOutputDir($sitePath);

        return $this->validStaticFilePath($sitePath . $outputDir . $uri, $sitePath);
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        $outputDir = $this->getOutputDir($sitePath);

        $_SERVER['SCRIPT_FILENAME'] = $sitePath . $outputDir . '/index.html';
        $_SERVER['SCRIPT_NAME'] = '/index.html';
        $_SERVER['DOCUMENT_ROOT'] = $sitePath . $outputDir;

        return $sitePath . $outputDir . '/index.html';
    }

    /**
     * Determine the output directory for the Sculpin installation.
     */
    protected function getOutputDir(string $sitePath): string
    {
        if (is_dir($sitePath . '/output_dev')) {
            return '/output_dev';
        }

        return '/output_prod';
    }
}
