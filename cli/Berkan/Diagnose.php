<?php

namespace Berkan;

use Berkan\Contracts\WebServer;

class Diagnose
{
    public CommandLine $cli;
    public Filesystem $files;
    public Configuration $config;
    public Brew $brew;
    public WebServer $webServer;
    public PhpFpm $phpFpm;
    public DnsMasq $dnsMasq;

    /**
     * Create a new Diagnose instance.
     */
    public function __construct(
        CommandLine $cli,
        Filesystem $files,
        Configuration $config,
        Brew $brew,
        WebServer $webServer,
        PhpFpm $phpFpm,
        DnsMasq $dnsMasq
    ) {
        $this->cli = $cli;
        $this->files = $files;
        $this->config = $config;
        $this->brew = $brew;
        $this->webServer = $webServer;
        $this->phpFpm = $phpFpm;
        $this->dnsMasq = $dnsMasq;
    }

    /**
     * Run diagnostics and return the results.
     */
    public function run(): array
    {
        $results = [];

        $results['Berkan Version'] = Berkan::version();
        $results['PHP Version'] = PHP_VERSION;
        $results['PHP Binary'] = PHP_BINARY;
        $results['Operating System'] = PHP_OS . ' ' . php_uname('r');
        $results['Homebrew Prefix'] = BREW_PREFIX;
        $results['Berkan Config Path'] = $this->config->homePath();
        $results['TLD'] = $this->config->read()['tld'] ?? 'test';
        $results['Loopback'] = $this->config->read()['loopback'] ?? BERKAN_LOOPBACK;
        $results['Web Server'] = $this->config->read()['web_server'] ?? 'apache';

        // Check services
        $results[$this->webServer->serviceName() . ' Status'] = $this->webServer->status();
        $results['PHP-FPM Status'] = $this->phpFpm->status();
        $results['DnsMasq Status'] = $this->dnsMasq->status();

        // Check config files
        $results[$this->webServer->serviceName() . ' Config'] = $this->files->exists($this->webServer->confPath()) ? 'Found' : 'Missing';

        // Check socket
        $socketPath = $this->phpFpm->socketPath();
        $results['PHP-FPM Socket'] = $this->files->exists($socketPath) ? 'Found' : 'Missing';

        // Check DNS resolver
        $tld = $this->config->read()['tld'] ?? 'test';
        $resolverPath = '/etc/resolver/' . $tld;
        $results['DNS Resolver'] = $this->files->exists($resolverPath) ? 'Found' : 'Missing';

        // Check installed PHP versions
        $results['Installed PHP'] = implode(', ', $this->brew->supportedPhpVersions());

        // Check linked PHP
        try {
            $results['Linked PHP'] = $this->brew->linkedPhp();
        } catch (\Exception $e) {
            $results['Linked PHP'] = 'Not found';
        }

        // Web server config test
        $configTest = trim($this->cli->run($this->webServer->configTestCommand()));
        $results[$this->webServer->serviceName() . ' Config Test'] = $configTest;

        // Check parked paths
        $paths = $this->config->parkedPaths();
        $results['Parked Paths'] = count($paths) > 0 ? implode(', ', $paths) : 'None';

        // DNS resolution test
        $dnsResult = trim($this->cli->run("dig +short {$tld} @127.0.0.1 2>/dev/null"));
        $results['DNS Test'] = $dnsResult ?: 'No response';

        // Check installed databases
        $config = $this->config->read();
        $databases = $config['databases'] ?? [];
        $results['Installed Databases'] = ! empty($databases) ? implode(', ', $databases) : 'None';

        return $results;
    }

    /**
     * Display diagnostics.
     */
    public function display(): void
    {
        $results = $this->run();

        $rows = [];
        foreach ($results as $label => $value) {
            $rows[] = [$label, $value];
        }

        output(PHP_EOL . 'Berkan Diagnostics' . PHP_EOL . str_repeat('=', 50));
        table(['Check', 'Result'], $rows);
    }
}
