<?php

namespace Berkan;

class PhpFpm
{
    public Brew $brew;
    public CommandLine $cli;
    public Filesystem $files;
    public Configuration $config;

    const SUPPORTED_PHP_FORMULAE = [
        'php',
        'php@8.4',
        'php@8.3',
        'php@8.2',
        'php@8.1',
        'php@8.0',
        'php@7.4',
        'php@7.3',
        'php@7.2',
        'php@7.1',
        'php@7.0',
        'php@5.6',
    ];

    /**
     * Create a new PhpFpm instance.
     */
    public function __construct(Brew $brew, CommandLine $cli, Filesystem $files, Configuration $config)
    {
        $this->brew = $brew;
        $this->cli = $cli;
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Install and configure PHP-FPM.
     */
    public function install(): void
    {
        if (! $this->brew->hasInstalledPhp()) {
            $this->brew->ensureInstalled('php');
        }

        $this->files->ensureDirExists($this->config->homePath() . '/Log', user());

        $this->installPrependFile();
        $this->installConfiguration();
        $this->restart();
    }

    /**
     * Install the Berkan prepend file for error display control.
     */
    public function installPrependFile(): void
    {
        $source = __DIR__ . '/../stubs/berkan_prepend.php';
        $destination = $this->config->homePath() . '/berkan_prepend.php';

        if ($this->files->exists($source)) {
            $this->files->putAsUser($destination, $this->files->get($source));
        }
    }

    /**
     * Ensure all active FPM pool configs have the auto_prepend_file directive.
     */
    public function ensurePrependInPools(): void
    {
        $prependPath = $this->config->homePath() . '/berkan_prepend.php';
        $prependLine = 'php_admin_value[auto_prepend_file] = ' . $prependPath;

        foreach ($this->brew->supportedPhpVersions() as $formula) {
            $versionNumber = str_replace(['php@', 'php'], '', $formula) ?: $this->brew->getPhpVersion($formula);

            $configs = [
                BREW_PREFIX . '/etc/php/' . $versionNumber . '/php-fpm.d/berkan-fpm.conf',
                BREW_PREFIX . '/etc/php/' . $versionNumber . '/php-fpm.d/berkan-isolated-fpm.conf',
            ];

            foreach ($configs as $configFile) {
                if ($this->files->exists($configFile)) {
                    $content = $this->files->get($configFile);
                    if (strpos($content, 'auto_prepend_file') === false) {
                        $this->files->put($configFile, $content . "\n" . $prependLine . "\n");
                    }
                }
            }
        }

        // Restart all running PHP-FPM services
        foreach ($this->brew->supportedPhpVersions() as $formula) {
            if ($this->brew->isStartedService($formula)) {
                $this->brew->restartService($formula);
            }
        }
    }

    /**
     * Install the PHP-FPM configuration.
     */
    public function installConfiguration(?string $phpVersion = null, bool $isolated = false): void
    {
        $phpVersion = $phpVersion ?: $this->brew->getLinkedPhpFormula();

        if (! $phpVersion) {
            warning('No PHP installation found.');
            return;
        }

        if ($isolated) {
            $contents = $this->files->get(__DIR__ . '/../stubs/etc-phpfpm-berkan-isolated.conf');
        } else {
            $contents = $this->files->get(__DIR__ . '/../stubs/etc-phpfpm-berkan.conf');
        }

        $fpmConfigPath = $this->fpmConfigPath($phpVersion);

        if ($isolated) {
            $fpmConfigPath = str_replace('berkan-fpm.conf', 'berkan-isolated-fpm.conf', $fpmConfigPath);
        }

        $this->files->ensureDirExists(dirname($fpmConfigPath), user());

        $configContent = $this->buildFpmConfig($contents, $phpVersion);

        if ($isolated) {
            $versionNumber = str_replace(['php@', 'php'], '', $phpVersion) ?: $this->brew->getPhpVersion($phpVersion);
            $configContent = str_replace(
                ['BERKAN_PHP_VERSION', 'BERKAN_ISOLATED_SOCKET'],
                [$versionNumber, $this->isolatedSocketPath($phpVersion)],
                $configContent
            );
        }

        $this->files->put($fpmConfigPath, $configContent);

        // Install error log configuration
        $this->installErrorLogConfiguration($phpVersion);

        // Install memory limits configuration
        $this->installMemoryLimitsConfiguration($phpVersion);
    }

    /**
     * Build FPM configuration by replacing stub placeholders.
     */
    protected function buildFpmConfig(string $contents, string $phpVersion): string
    {
        $homePath = $this->config->homePath();

        return str_replace(
            [
                'BERKAN_USER',
                'BERKAN_GROUP',
                'BERKAN_HOME_PATH',
            ],
            [
                user(),
                'staff',
                $homePath,
            ],
            $contents
        );
    }

    /**
     * Install the PHP-FPM error log configuration.
     */
    protected function installErrorLogConfiguration(string $phpVersion): void
    {
        $contents = $this->files->get(__DIR__ . '/../stubs/etc-phpfpm-error_log.ini');

        $confDPath = $this->phpIniScanDir($phpVersion);

        if ($confDPath) {
            $this->files->ensureDirExists($confDPath);
            $this->files->put(
                $confDPath . '/berkan-error-log.ini',
                str_replace('BERKAN_HOME_PATH', $this->config->homePath(), $contents)
            );
        }
    }

    /**
     * Install the memory limits configuration.
     */
    protected function installMemoryLimitsConfiguration(string $phpVersion): void
    {
        $confDPath = $this->phpIniScanDir($phpVersion);

        if ($confDPath) {
            $this->files->ensureDirExists($confDPath);
            $this->files->copy(
                __DIR__ . '/../stubs/php-memory-limits.ini',
                $confDPath . '/berkan-memory-limits.ini'
            );
        }
    }

    /**
     * Restart the PHP-FPM process.
     */
    public function restart(): void
    {
        $phpVersion = $this->brew->getLinkedPhpFormula();

        if ($phpVersion) {
            $this->brew->restartService($phpVersion);
        }
    }

    /**
     * Stop the PHP-FPM process.
     */
    public function stop(): void
    {
        foreach (static::SUPPORTED_PHP_FORMULAE as $formula) {
            if ($this->brew->installed($formula)) {
                $this->brew->stopService($formula);
            }
        }
    }

    /**
     * Start the PHP-FPM process.
     */
    public function start(): void
    {
        $phpVersion = $this->brew->getLinkedPhpFormula();

        if ($phpVersion) {
            $this->brew->startService($phpVersion);
        }
    }

    /**
     * Determine if PHP-FPM is running.
     */
    public function isRunning(): bool
    {
        $phpVersion = $this->brew->getLinkedPhpFormula();

        if (! $phpVersion) {
            return false;
        }

        return $this->brew->isStartedService($phpVersion);
    }

    /**
     * Get the PHP-FPM socket path.
     */
    public function socketPath(): string
    {
        return $this->config->homePath() . '/berkan.sock';
    }

    /**
     * Get the isolated PHP-FPM socket path for a specific version.
     */
    public function isolatedSocketPath(string $phpVersion): string
    {
        $versionNumber = str_replace(['php@', 'php'], '', $phpVersion) ?: $this->brew->getPhpVersion($phpVersion);
        return $this->config->homePath() . '/berkan-' . $versionNumber . '.sock';
    }

    /**
     * Get the status of PHP-FPM.
     */
    public function status(): string
    {
        return $this->isRunning() ? 'Running' : 'Stopped';
    }

    /**
     * Get the FPM configuration file path for the given PHP version.
     */
    public function fpmConfigPath(string $phpVersion): string
    {
        $versionNumber = str_replace(['php@', 'php'], '', $phpVersion) ?: $this->brew->getPhpVersion($phpVersion);

        return BREW_PREFIX . '/etc/php/' . $versionNumber . '/php-fpm.d/berkan-fpm.conf';
    }

    /**
     * Get the conf.d directory for the given PHP version.
     */
    protected function phpIniScanDir(string $phpVersion): ?string
    {
        $versionNumber = str_replace(['php@', 'php'], '', $phpVersion) ?: $this->brew->getPhpVersion($phpVersion);

        $scanDir = BREW_PREFIX . '/etc/php/' . $versionNumber . '/conf.d';

        return $scanDir;
    }

    /**
     * Utilise a specific version of PHP.
     */
    public function useVersion(string $version): void
    {
        $formula = starts_with($version, 'php') ? $version : 'php@' . $version;

        if (! $this->brew->installed($formula)) {
            $this->brew->installOrFail($formula);
        }

        // Unlink all PHP versions
        foreach ($this->brew->supportedPhpVersions() as $installedVersion) {
            $this->brew->unlink($installedVersion);
        }

        // Link the requested version
        $this->brew->link($formula, true);

        // Install configuration for new version
        $this->installConfiguration($formula);

        // Restart all services
        $this->stopAllPhpServices();
        $this->brew->restartService($formula);

        info("PHP version changed to {$formula}.");
    }

    /**
     * Stop all PHP FPM services.
     */
    protected function stopAllPhpServices(): void
    {
        foreach ($this->brew->supportedPhpVersions() as $version) {
            $this->brew->stopService($version);
        }
    }

    /**
     * Get the currently used PHP version formula.
     */
    public function currentVersion(): ?string
    {
        return $this->brew->getLinkedPhpFormula();
    }

    /**
     * Install configuration for a specific PHP version (for isolation).
     */
    public function isolateVersion(string $phpVersion): void
    {
        if (! $this->brew->installed($phpVersion)) {
            $this->brew->installOrFail($phpVersion);
        }

        $this->installConfiguration($phpVersion, true);
        $this->brew->restartService($phpVersion);
    }

    /**
     * Remove isolation for a specific PHP version.
     */
    public function removeIsolation(string $phpVersion): void
    {
        $fpmConfigPath = str_replace('berkan-fpm.conf', 'berkan-isolated-fpm.conf', $this->fpmConfigPath($phpVersion));

        if ($this->files->exists($fpmConfigPath)) {
            $this->files->unlink($fpmConfigPath);
        }

        if ($this->brew->isStartedService($phpVersion)) {
            $this->brew->restartService($phpVersion);
        }
    }
}
