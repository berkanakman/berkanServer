<?php

namespace Berkan;

class Configuration
{
    public Filesystem $files;

    public const BERKAN_HOME_PATH = '/.config/berkan';

    /**
     * Create a new Configuration instance.
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Install the Berkan configuration directory and files.
     */
    public function install(): void
    {
        $this->createConfigurationDirectory();
        $this->createDriversDirectory();
        $this->createSitesDirectory();
        $this->createCertificatesDirectory();
        $this->createLogDirectory();
        $this->createDnsmasqDirectory();
        $this->writeBaseConfiguration();

        $this->files->chown($this->path(), user());
    }

    /**
     * Uninstall the Berkan configuration.
     */
    public function uninstall(): void
    {
        $this->files->remove($this->path());
    }

    /**
     * Create the Berkan configuration directory.
     */
    public function createConfigurationDirectory(): void
    {
        $this->files->ensureDirExists($this->path(), user());
    }

    /**
     * Create the Apache configuration directory.
     */
    public function createApacheDirectory(): void
    {
        $this->files->ensureDirExists($this->path() . '/Apache', user());
    }

    /**
     * Create the Nginx configuration directory.
     */
    public function createNginxDirectory(): void
    {
        $this->files->ensureDirExists($this->path() . '/Nginx', user());
    }

    /**
     * Create the web server configuration directory based on config.
     */
    public function createWebServerDirectory(): void
    {
        $config = $this->read();
        $webServer = $config['web_server'] ?? 'apache';

        if ($webServer === 'nginx') {
            $this->createNginxDirectory();
        } else {
            $this->createApacheDirectory();
        }
    }

    /**
     * Create the Drivers directory.
     */
    public function createDriversDirectory(): void
    {
        if ($this->files->isDir($driverPath = $this->path() . '/Drivers')) {
            return;
        }

        $this->files->mkdirAsUser($driverPath);

        $this->files->putAsUser(
            $driverPath . '/SampleBerkanDriver.php',
            $this->files->get(__DIR__ . '/../stubs/SampleBerkanDriver.php')
        );
    }

    /**
     * Create the Sites directory.
     */
    public function createSitesDirectory(): void
    {
        $this->files->ensureDirExists($this->path() . '/Sites', user());
    }

    /**
     * Create the Certificates directory.
     */
    public function createCertificatesDirectory(): void
    {
        $this->files->ensureDirExists($this->path() . '/Certificates', user());
    }

    /**
     * Create the Log directory.
     */
    public function createLogDirectory(): void
    {
        $this->files->ensureDirExists($this->path() . '/Log', user());
    }

    /**
     * Create the dnsmasq.d directory.
     */
    public function createDnsmasqDirectory(): void
    {
        $this->files->ensureDirExists($this->path() . '/dnsmasq.d', user());
    }

    /**
     * Write the base, initial configuration for Berkan.
     */
    public function writeBaseConfiguration(): void
    {
        if (! $this->files->exists($this->path() . '/config.json')) {
            $this->write([
                'tld' => 'test',
                'loopback' => BERKAN_LOOPBACK,
                'paths' => [],
                'web_server' => 'apache',
                'php_versions' => ['8.4'],
                'databases' => [],
            ]);
        }
    }

    /**
     * Add the given path to the configuration.
     */
    public function addPath(string $path, bool $prepend = false): void
    {
        $config = $this->read();

        if (! in_array($path, $config['paths'])) {
            if ($prepend) {
                array_unshift($config['paths'], $path);
            } else {
                $config['paths'][] = $path;
            }

            $this->write($config);
        }
    }

    /**
     * Remove the given path from the configuration.
     */
    public function removePath(string $path): void
    {
        $config = $this->read();

        if (($key = array_search($path, $config['paths'])) !== false) {
            unset($config['paths'][$key]);
            $config['paths'] = array_values($config['paths']);
            $this->write($config);
        }
    }

    /**
     * Prune all non-existent paths from the configuration.
     */
    public function prune(): void
    {
        $config = $this->read();

        if (! isset($config['paths'])) {
            return;
        }

        $config['paths'] = collect($config['paths'])->filter(function ($path) {
            return $this->files->isDir($path);
        })->values()->all();

        $this->write($config);
    }

    /**
     * Read the configuration file.
     */
    public function read(): array
    {
        $configPath = $this->path() . '/config.json';

        if (! $this->files->exists($configPath)) {
            return [
                'tld' => 'test',
                'loopback' => BERKAN_LOOPBACK,
                'paths' => [],
                'web_server' => 'apache',
                'php_versions' => ['8.4'],
                'databases' => [],
            ];
        }

        return json_decode($this->files->get($configPath), true);
    }

    /**
     * Update a specific key in the configuration file.
     */
    public function updateKey(string $key, mixed $value): array
    {
        $config = $this->read();
        $config[$key] = $value;
        $this->write($config);

        return $config;
    }

    /**
     * Write the given configuration to disk.
     */
    public function write(array $config): void
    {
        $this->files->putAsUser(
            $this->path() . '/config.json',
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }

    /**
     * Get the configuration file path.
     */
    public function path(): string
    {
        return $this->homePath();
    }

    /**
     * Get the Berkan home path.
     */
    public function homePath(): string
    {
        return $_SERVER['HOME'] . static::BERKAN_HOME_PATH;
    }

    /**
     * Get the list of parked paths.
     */
    public function parkedPaths(): array
    {
        $config = $this->read();

        return $config['paths'] ?? [];
    }
}
