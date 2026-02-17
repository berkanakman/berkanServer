<?php

namespace Berkan\Drivers;

abstract class BerkanDriver
{
    /**
     * Determine if the driver serves the request.
     */
    abstract public function serves(string $sitePath, string $siteName, string $uri): bool;

    /**
     * Determine if the incoming request is for a static file.
     *
     * @return string|false
     */
    abstract public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false;

    /**
     * Get the fully resolved path to the application's front controller.
     */
    abstract public function frontControllerPath(string $sitePath, string $siteName, string $uri): string;

    /**
     * Find a driver that can serve the incoming request.
     */
    public static function assign(string $sitePath, string $siteName, string $uri): ?static
    {
        $dominated = static::driversIn(BERKAN_HOME_PATH . '/Drivers');
        $specific = static::driversIn(__DIR__ . '/Specific');
        $default = static::defaultDrivers();

        $drivers = array_merge($dominated, $specific, $default);

        foreach ($drivers as $driver) {
            try {
                $instance = new $driver;

                if ($instance->serves($sitePath, $siteName, $uri)) {
                    return $instance;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Get all of the driver classes in a given path.
     */
    public static function driversIn(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $drivers = [];

        foreach (scandir($path) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (! str_ends_with($file, '.php')) {
                continue;
            }

            if (str_contains($file, 'BerkanDriver') && $file !== 'BerkanDriver.php') {
                require_once $path . '/' . $file;
                $className = str_replace('.php', '', $file);

                // For files in the Specific directory, they might use the Berkan\Drivers\Specific namespace
                if (class_exists('Berkan\\Drivers\\Specific\\' . $className)) {
                    $drivers[] = 'Berkan\\Drivers\\Specific\\' . $className;
                } elseif (class_exists('Berkan\\Drivers\\' . $className)) {
                    $drivers[] = 'Berkan\\Drivers\\' . $className;
                } elseif (class_exists($className)) {
                    $drivers[] = $className;
                }
            }
        }

        return $drivers;
    }

    /**
     * Get the default drivers.
     */
    public static function defaultDrivers(): array
    {
        return [
            BasicWithPublicBerkanDriver::class,
            BasicBerkanDriver::class,
        ];
    }

    /**
     * Mutate the incoming URI.
     */
    public function mutateUri(string $uri): string
    {
        return $uri;
    }

    /**
     * Serve a static file at the given path.
     */
    public function serveStaticFile(string $staticFilePath, string $sitePath, string $siteName, string $uri): void
    {
        $mimeType = $this->getMimeType($staticFilePath);

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($staticFilePath));

        readfile($staticFilePath);
    }

    /**
     * Get the MIME type for the given file.
     */
    protected function getMimeType(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'html' => 'text/html',
            'htm' => 'text/html',
            'txt' => 'text/plain',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
        ];

        return $mimeTypes[$extension] ?? (mime_content_type($path) ?: 'application/octet-stream');
    }
}
