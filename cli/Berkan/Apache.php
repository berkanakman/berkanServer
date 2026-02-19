<?php

namespace Berkan;

use Berkan\Contracts\WebServer;

class Apache implements WebServer
{
    public Brew $brew;
    public CommandLine $cli;
    public Filesystem $files;
    public Configuration $config;
    public Site $site;

    /**
     * Create a new Apache instance.
     */
    public function __construct(Brew $brew, CommandLine $cli, Filesystem $files, Configuration $config, Site $site)
    {
        $this->brew = $brew;
        $this->cli = $cli;
        $this->files = $files;
        $this->config = $config;
        $this->site = $site;
    }

    /**
     * Install the Apache configuration files.
     */
    public function install(): void
    {
        $this->brew->ensureInstalled('httpd');
        $this->stop();

        $this->installConfiguration();
        $this->installServer();
        $this->installApacheDirectory();

        $this->restart();
    }

    /**
     * Install the main Apache configuration.
     */
    public function installConfiguration(): void
    {
        $contents = $this->files->get(__DIR__ . '/../stubs/httpd.conf');

        $this->files->put(
            $this->apacheConfPath(),
            $this->buildConfiguration($contents)
        );
    }

    /**
     * Build the Apache configuration by replacing stub placeholders.
     */
    public function buildConfiguration(string $contents): string
    {
        $config = $this->config->read();

        return str_replace(
            [
                'BERKAN_HOMEBREW_PATH',
                'BERKAN_LOOPBACK',
                'BERKAN_HTTP_PORT',
                'BERKAN_HTTPS_PORT',
                'BERKAN_USER',
                'BERKAN_GROUP',
                'BERKAN_HOME_PATH',
                'BERKAN_SERVER_PATH',
            ],
            [
                BREW_PREFIX,
                $config['loopback'] ?? BERKAN_LOOPBACK,
                $config['http_port'] ?? '80',
                $config['https_port'] ?? '443',
                user(),
                'staff',
                $this->config->homePath(),
                realpath(__DIR__ . '/../../'),
            ],
            $contents
        );
    }

    /**
     * Install the default Berkan VirtualHost server.
     */
    public function installServer(): void
    {
        $this->config->createApacheDirectory();

        $this->files->putAsUser(
            $this->config->homePath() . '/Apache/berkan.conf',
            $this->buildConfiguration(
                $this->files->get(__DIR__ . '/../stubs/berkan.conf')
            )
        );
    }

    /**
     * Install the Apache configuration directory.
     */
    public function installApacheDirectory(): void
    {
        $this->config->createApacheDirectory();
    }

    /**
     * Restart the Apache service.
     */
    public function restart(): void
    {
        $configTest = $this->cli->run($this->configTestCommand());

        if (! str_contains(strtolower($configTest), 'syntax ok')) {
            warning('Apache configuration test failed:');
            warning($configTest);

            return;
        }

        $this->brew->restartService($this->brewServiceName());
    }

    /**
     * Stop the Apache service.
     */
    public function stop(): void
    {
        info('Stopping ' . $this->brewServiceName() . '...');
        $this->brew->stopService($this->brewServiceName());
    }

    /**
     * Start the Apache service.
     */
    public function start(): void
    {
        info('Starting ' . $this->brewServiceName() . '...');
        $this->brew->startService($this->brewServiceName());
    }

    /**
     * Determine if Apache is running.
     */
    public function isRunning(): bool
    {
        return $this->brew->isStartedService($this->brewServiceName());
    }

    /**
     * Uninstall the Apache configuration.
     */
    public function uninstall(): void
    {
        $this->stop();

        // Restore original Apache config if backup exists
        $backupPath = $this->apacheConfPath() . '.bak.berkan';
        if ($this->files->exists($backupPath)) {
            $this->files->copy($backupPath, $this->apacheConfPath());
        }

        // Remove Berkan Apache directory
        $this->files->remove($this->config->homePath() . '/Apache');
    }

    /**
     * Get all configured sites from the Apache directory.
     */
    public function configuredSites(): array
    {
        $apachePath = $this->config->homePath() . '/Apache';

        if (! $this->files->isDir($apachePath)) {
            return [];
        }

        return collect($this->files->scandir($apachePath))
            ->filter(function ($file) {
                return str_ends_with($file, '.conf') && $file !== 'berkan.conf';
            })->values()->all();
    }

    /**
     * Rewrite the secure Apache files with updated information.
     */
    public function rewriteSecureFiles(): void
    {
        $config = $this->config->read();
        $tld = $config['tld'] ?? 'test';

        $this->site->resecureForNewTld($tld, $tld);
    }

    /**
     * Get the Apache configuration file path.
     */
    public function apacheConfPath(): string
    {
        return BREW_PREFIX . '/etc/httpd/httpd.conf';
    }

    /**
     * Get the Apache error log path.
     */
    public function errorLogPath(): string
    {
        return $this->config->homePath() . '/Log/apache-error.log';
    }

    /**
     * Get the status of Apache for diagnostics.
     */
    public function status(): string
    {
        if ($this->isRunning()) {
            return 'Running';
        }

        return 'Stopped';
    }

    /**
     * Create a site-specific Apache configuration.
     */
    public function installSite(string $url, string $siteConf): void
    {
        $this->config->createApacheDirectory();

        $this->files->putAsUser(
            $this->config->homePath() . '/Apache/' . $url . '.conf',
            $siteConf
        );
    }

    /**
     * Remove a site-specific Apache configuration.
     */
    public function removeSite(string $url): void
    {
        $confPath = $this->config->homePath() . '/Apache/' . $url . '.conf';

        if ($this->files->exists($confPath)) {
            $this->files->unlink($confPath);
        }
    }

    /**
     * Get the service display name.
     */
    public function serviceName(): string
    {
        return 'Apache (httpd)';
    }

    /**
     * Get the Homebrew service name.
     */
    public function brewServiceName(): string
    {
        return 'httpd';
    }

    /**
     * Get the configuration test command.
     */
    public function configTestCommand(): string
    {
        return 'sudo -u "' . user() . '" ' . BREW_PREFIX . '/bin/httpd -t -f ' . $this->apacheConfPath() . ' 2>&1';
    }

    /**
     * Get the main configuration file path.
     */
    public function confPath(): string
    {
        return $this->apacheConfPath();
    }
}
