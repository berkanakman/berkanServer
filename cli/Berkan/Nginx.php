<?php

namespace Berkan;

use Berkan\Contracts\WebServer;

class Nginx implements WebServer
{
    public Brew $brew;
    public CommandLine $cli;
    public Filesystem $files;
    public Configuration $config;
    public Site $site;

    /**
     * Create a new Nginx instance.
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
     * Install the Nginx configuration files.
     */
    public function install(): void
    {
        $this->brew->ensureInstalled('nginx');
        $this->stop();

        $this->installConfiguration();
        $this->installServer();
        $this->installNginxDirectory();

        $this->restart();
    }

    /**
     * Install the main Nginx configuration.
     */
    public function installConfiguration(): void
    {
        $contents = $this->files->get(__DIR__ . '/../stubs/nginx.conf');

        $this->files->put(
            $this->confPath(),
            $this->buildConfiguration($contents)
        );
    }

    /**
     * Build the Nginx configuration by replacing stub placeholders.
     */
    public function buildConfiguration(string $contents): string
    {
        $config = $this->config->read();

        return str_replace(
            [
                'VALET_HOMEBREW_PATH',
                'VALET_LOOPBACK',
                'VALET_HTTP_PORT',
                'VALET_HTTPS_PORT',
                'VALET_USER',
                'VALET_GROUP',
                'VALET_HOME_PATH',
                'VALET_SERVER_PATH',
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
     * Install the default Berkan Nginx server configuration.
     */
    public function installServer(): void
    {
        $this->config->createNginxDirectory();

        $this->files->putAsUser(
            $this->config->homePath() . '/Nginx/berkan.conf',
            $this->buildConfiguration(
                $this->files->get(__DIR__ . '/../stubs/nginx-berkan.conf')
            )
        );
    }

    /**
     * Install the Nginx configuration directory.
     */
    public function installNginxDirectory(): void
    {
        $this->config->createNginxDirectory();
    }

    /**
     * Restart the Nginx service.
     */
    public function restart(): void
    {
        $configTest = $this->cli->run($this->configTestCommand());

        if (! str_contains(strtolower($configTest), 'successful') && ! str_contains(strtolower($configTest), 'syntax is ok')) {
            warning('Nginx configuration test failed:');
            warning($configTest);

            return;
        }

        $this->brew->restartService($this->brewServiceName());
    }

    /**
     * Stop the Nginx service.
     */
    public function stop(): void
    {
        info('Stopping ' . $this->brewServiceName() . '...');
        $this->brew->stopService($this->brewServiceName());
    }

    /**
     * Start the Nginx service.
     */
    public function start(): void
    {
        info('Starting ' . $this->brewServiceName() . '...');
        $this->brew->startService($this->brewServiceName());
    }

    /**
     * Determine if Nginx is running.
     */
    public function isRunning(): bool
    {
        return $this->brew->isStartedService($this->brewServiceName());
    }

    /**
     * Uninstall the Nginx configuration.
     */
    public function uninstall(): void
    {
        $this->stop();

        // Restore original Nginx config if backup exists
        $backupPath = $this->confPath() . '.bak.berkan';
        if ($this->files->exists($backupPath)) {
            $this->files->copy($backupPath, $this->confPath());
        }

        // Remove Berkan Nginx directory
        $this->files->remove($this->config->homePath() . '/Nginx');
    }

    /**
     * Get all configured sites from the Nginx directory.
     */
    public function configuredSites(): array
    {
        $nginxPath = $this->config->homePath() . '/Nginx';

        if (! $this->files->isDir($nginxPath)) {
            return [];
        }

        return collect($this->files->scandir($nginxPath))
            ->filter(function ($file) {
                return str_ends_with($file, '.conf') && $file !== 'berkan.conf';
            })->values()->all();
    }

    /**
     * Rewrite the secure Nginx files with updated information.
     */
    public function rewriteSecureFiles(): void
    {
        $config = $this->config->read();
        $tld = $config['tld'];

        $this->site->resecureForNewTld($tld, $tld);
    }

    /**
     * Get the Nginx error log path.
     */
    public function errorLogPath(): string
    {
        return $this->config->homePath() . '/Log/nginx-error.log';
    }

    /**
     * Get the status of Nginx for diagnostics.
     */
    public function status(): string
    {
        if ($this->isRunning()) {
            return 'Running';
        }

        return 'Stopped';
    }

    /**
     * Create a site-specific Nginx configuration.
     */
    public function installSite(string $url, string $siteConf): void
    {
        $this->config->createNginxDirectory();

        $this->files->putAsUser(
            $this->config->homePath() . '/Nginx/' . $url . '.conf',
            $siteConf
        );
    }

    /**
     * Remove a site-specific Nginx configuration.
     */
    public function removeSite(string $url): void
    {
        $confPath = $this->config->homePath() . '/Nginx/' . $url . '.conf';

        if ($this->files->exists($confPath)) {
            $this->files->unlink($confPath);
        }
    }

    /**
     * Get the service display name.
     */
    public function serviceName(): string
    {
        return 'Nginx';
    }

    /**
     * Get the Homebrew service name.
     */
    public function brewServiceName(): string
    {
        return 'nginx';
    }

    /**
     * Get the configuration test command.
     */
    public function configTestCommand(): string
    {
        return 'sudo nginx -t 2>&1';
    }

    /**
     * Get the main configuration file path.
     */
    public function confPath(): string
    {
        return BREW_PREFIX . '/etc/nginx/nginx.conf';
    }
}
