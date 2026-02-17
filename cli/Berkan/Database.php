<?php

namespace Berkan;

class Database
{
    public Brew $brew;
    public CommandLine $cli;
    public Filesystem $files;
    public Configuration $config;

    const SUPPORTED_DATABASES = [
        'mysql' => ['formula' => 'mysql', 'service' => 'mysql', 'label' => 'MySQL'],
        'postgresql' => ['formula' => 'postgresql@17', 'service' => 'postgresql@17', 'label' => 'PostgreSQL'],
        'mongodb' => ['formula' => 'mongodb-community', 'service' => 'mongodb-community', 'label' => 'MongoDB'],
        'redis' => ['formula' => 'redis', 'service' => 'redis', 'label' => 'Redis'],
    ];

    /**
     * Create a new Database instance.
     */
    public function __construct(Brew $brew, CommandLine $cli, Filesystem $files, Configuration $config)
    {
        $this->brew = $brew;
        $this->cli = $cli;
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Install a database.
     */
    public function install(string $database): void
    {
        $dbInfo = static::SUPPORTED_DATABASES[$database] ?? null;

        if (! $dbInfo) {
            warning("Unsupported database: {$database}");
            return;
        }

        // MongoDB requires a tap
        if ($database === 'mongodb') {
            info('Tapping mongodb/brew...');
            $this->brew->tap(['mongodb/brew']);
        }

        $this->brew->ensureInstalled($dbInfo['formula']);

        $this->start($database);

        // Save to config
        $config = $this->config->read();
        $databases = $config['databases'] ?? [];

        if (! in_array($database, $databases)) {
            $databases[] = $database;
            $this->config->updateKey('databases', $databases);
        }

        info("{$dbInfo['label']} has been installed and started.");
    }

    /**
     * Uninstall a database.
     */
    public function uninstall(string $database): void
    {
        $dbInfo = static::SUPPORTED_DATABASES[$database] ?? null;

        if (! $dbInfo) {
            warning("Unsupported database: {$database}");
            return;
        }

        $this->stop($database);

        info("Uninstalling {$dbInfo['label']}...");
        $this->cli->runAsUser('brew uninstall ' . $dbInfo['formula']);

        // Remove from config
        $config = $this->config->read();
        $databases = $config['databases'] ?? [];

        if (($key = array_search($database, $databases)) !== false) {
            unset($databases[$key]);
            $this->config->updateKey('databases', array_values($databases));
        }

        info("{$dbInfo['label']} has been uninstalled.");
    }

    /**
     * Start a database or all installed databases.
     */
    public function start(?string $database = null): void
    {
        if ($database) {
            $dbInfo = static::SUPPORTED_DATABASES[$database] ?? null;
            if ($dbInfo) {
                $this->brew->startService($dbInfo['service']);
            }
            return;
        }

        foreach ($this->installed() as $db) {
            $this->start($db);
        }
    }

    /**
     * Stop a database or all installed databases.
     */
    public function stop(?string $database = null): void
    {
        if ($database) {
            $dbInfo = static::SUPPORTED_DATABASES[$database] ?? null;
            if ($dbInfo) {
                $this->brew->stopService($dbInfo['service']);
            }
            return;
        }

        foreach ($this->installed() as $db) {
            $this->stop($db);
        }
    }

    /**
     * Restart a database or all installed databases.
     */
    public function restart(?string $database = null): void
    {
        if ($database) {
            $dbInfo = static::SUPPORTED_DATABASES[$database] ?? null;
            if ($dbInfo) {
                $this->brew->restartService($dbInfo['service']);
            }
            return;
        }

        foreach ($this->installed() as $db) {
            $this->restart($db);
        }
    }

    /**
     * Determine if a database is running.
     */
    public function isRunning(string $database): bool
    {
        $dbInfo = static::SUPPORTED_DATABASES[$database] ?? null;

        if (! $dbInfo) {
            return false;
        }

        return $this->brew->isStartedService($dbInfo['service']);
    }

    /**
     * Get the status of a database.
     */
    public function status(string $database): string
    {
        return $this->isRunning($database) ? 'Running' : 'Stopped';
    }

    /**
     * Get all installed databases from config.
     */
    public function installed(): array
    {
        $config = $this->config->read();

        return $config['databases'] ?? [];
    }

    /**
     * Get list of all supported databases with their status.
     */
    public function list(): array
    {
        $installed = $this->installed();
        $result = [];

        foreach (static::SUPPORTED_DATABASES as $key => $dbInfo) {
            $isInstalled = in_array($key, $installed);

            $result[] = [
                'name' => $key,
                'label' => $dbInfo['label'],
                'installed' => $isInstalled,
                'status' => $isInstalled ? $this->status($key) : 'Not Installed',
            ];
        }

        return $result;
    }
}
