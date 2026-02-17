<?php

namespace Berkan;

class DnsMasq
{
    public Brew $brew;
    public CommandLine $cli;
    public Filesystem $files;
    public Configuration $config;

    /**
     * Create a new DnsMasq instance.
     */
    public function __construct(Brew $brew, CommandLine $cli, Filesystem $files, Configuration $config)
    {
        $this->brew = $brew;
        $this->cli = $cli;
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Install and configure DnsMasq.
     */
    public function install(string $tld = 'test'): void
    {
        $this->brew->ensureInstalled('dnsmasq');

        // Configure dnsmasq
        $this->configureDnsmasq($tld);

        // Create resolver
        $this->createResolver($tld);

        // Restart dnsmasq
        $this->brew->restartService('dnsmasq');
    }

    /**
     * Configure DnsMasq to resolve the given TLD.
     */
    public function configureDnsmasq(string $tld): void
    {
        $dnsmasqConf = BREW_PREFIX . '/etc/dnsmasq.conf';
        $berkanConf = $this->config->homePath() . '/dnsmasq.d/tld-' . $tld . '.conf';

        // Ensure the custom config directory is included
        $this->appendCustomConfigInclude($dnsmasqConf);

        // Write the TLD config
        $this->files->putAsUser(
            $berkanConf,
            'address=/.' . $tld . '/' . ($this->config->read()['loopback'] ?? BERKAN_LOOPBACK) . PHP_EOL
        );
    }

    /**
     * Append the custom config include to the main dnsmasq.conf.
     */
    protected function appendCustomConfigInclude(string $dnsmasqConf): void
    {
        $includeLine = 'conf-dir=' . $this->config->homePath() . '/dnsmasq.d/,*.conf';

        if ($this->files->exists($dnsmasqConf)) {
            $contents = $this->files->get($dnsmasqConf);

            if (! str_contains($contents, $includeLine)) {
                $this->files->append($dnsmasqConf, PHP_EOL . $includeLine . PHP_EOL);
            }
        } else {
            $this->files->put($dnsmasqConf, $includeLine . PHP_EOL);
        }
    }

    /**
     * Create the resolver file for the given TLD.
     */
    public function createResolver(string $tld): void
    {
        $this->files->ensureDirExists('/etc/resolver');

        $this->files->put(
            '/etc/resolver/' . $tld,
            'nameserver ' . ($this->config->read()['loopback'] ?? BERKAN_LOOPBACK) . PHP_EOL
        );
    }

    /**
     * Update the TLD in the DnsMasq configuration.
     */
    public function updateTld(string $oldTld, string $newTld): void
    {
        // Remove old config
        $this->files->unlink($this->config->homePath() . '/dnsmasq.d/tld-' . $oldTld . '.conf');
        $this->files->unlink('/etc/resolver/' . $oldTld);

        // Install new config
        $this->install($newTld);
    }

    /**
     * Uninstall DnsMasq configuration.
     */
    public function uninstall(): void
    {
        $tld = $this->config->read()['tld'] ?? 'test';

        $this->brew->stopService('dnsmasq');

        $this->files->unlink($this->config->homePath() . '/dnsmasq.d/tld-' . $tld . '.conf');
        $this->files->unlink('/etc/resolver/' . $tld);
    }

    /**
     * Determine if DnsMasq is running.
     */
    public function isRunning(): bool
    {
        return $this->brew->isStartedService('dnsmasq');
    }

    /**
     * Get DnsMasq status.
     */
    public function status(): string
    {
        return $this->isRunning() ? 'Running' : 'Stopped';
    }

    /**
     * Restart DnsMasq.
     */
    public function restart(): void
    {
        $this->brew->restartService('dnsmasq');
    }

    /**
     * Stop DnsMasq.
     */
    public function stop(): void
    {
        $this->brew->stopService('dnsmasq');
    }
}
