<?php

namespace Berkan;

use Berkan\Contracts\WebServer;

class Site
{
    public ?WebServer $webServer = null;
    public Configuration $config;
    public CommandLine $cli;
    public Filesystem $files;

    /**
     * Create a new Site instance.
     */
    public function __construct(Configuration $config, CommandLine $cli, Filesystem $files)
    {
        $this->config = $config;
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Set the WebServer instance (used to avoid circular dependency).
     */
    public function setWebServer(WebServer $webServer): void
    {
        $this->webServer = $webServer;
    }

    /**
     * Get the WebServer instance, resolving lazily if needed.
     */
    protected function webServer(): WebServer
    {
        if (! $this->webServer) {
            $this->webServer = resolve(WebServer::class);
        }

        return $this->webServer;
    }

    /**
     * Get the current web server type from config.
     */
    protected function getWebServerType(): string
    {
        $config = $this->config->read();

        return $config['web_server'] ?? 'apache';
    }

    /**
     * Get the path to the linked Berkan sites.
     */
    public function sitesPath(): string
    {
        return $this->config->homePath() . '/Sites';
    }

    /**
     * Get the path to the certificates.
     */
    public function certificatesPath(): string
    {
        return $this->config->homePath() . '/Certificates';
    }

    /**
     * Link the current working directory with the given name.
     */
    public function link(string $target, string $name): string
    {
        $this->files->ensureDirExists($this->sitesPath(), user());

        $this->files->symlinkAsUser(
            $target,
            $this->sitesPath() . '/' . $name
        );

        return $name;
    }

    /**
     * Unlink the given symbolic link.
     */
    public function unlink(string $name): void
    {
        $path = $this->sitesPath() . '/' . $name;

        if ($this->files->exists($path)) {
            $this->files->unlink($path);
        }

        $this->removeWebServerConfig($name);
    }

    /**
     * Get all of the linked sites.
     */
    public function links(): \Illuminate\Support\Collection
    {
        $sitesPath = $this->sitesPath();

        $this->files->ensureDirExists($sitesPath, user());

        return collect($this->files->scandir($sitesPath))
            ->filter(function ($site) {
                return ! str_ends_with($site, '.proxy');
            })
            ->mapWithKeys(function ($site) use ($sitesPath) {
                $fullPath = $sitesPath . '/' . $site;

                if ($this->files->isLink($fullPath)) {
                    $linkTarget = $this->files->readLink($fullPath);
                    $realPath = $linkTarget !== false ? $linkTarget : $fullPath;
                } else {
                    $realPath = $fullPath;
                }

                return [$site => $realPath];
            });
    }

    /**
     * Scan a directory for projects and detect their frameworks.
     */
    public function scanProjects(string $path): array
    {
        $projects = [];

        foreach ($this->files->scandir($path) as $dir) {
            if (str_starts_with($dir, '.')) {
                continue;
            }

            $fullPath = $path . '/' . $dir;

            if (! $this->files->isDir($fullPath)) {
                continue;
            }

            $projects[] = [
                'name' => $dir,
                'path' => $fullPath,
                'framework' => $this->detectFramework($fullPath),
            ];
        }

        return $projects;
    }

    /**
     * Detect the framework used in a project directory.
     */
    public function detectFramework(string $sitePath): string
    {
        if (file_exists($sitePath . '/artisan')) return 'Laravel';
        if (file_exists($sitePath . '/bin/console') && file_exists($sitePath . '/public/index.php')) return 'Symfony';
        if (file_exists($sitePath . '/wp-config.php')) return 'WordPress';
        if (file_exists($sitePath . '/core/lib/Drupal.php')) return 'Drupal';
        if (file_exists($sitePath . '/bin/magento')) return 'Magento 2';
        if (file_exists($sitePath . '/craft')) return 'Craft CMS';
        if (file_exists($sitePath . '/configuration.php') && is_dir($sitePath . '/administrator')) return 'Joomla';
        if (file_exists($sitePath . '/composer.json')) return 'PHP (Composer)';
        if (glob($sitePath . '/*.php')) return 'Plain PHP';
        return 'Static/Unknown';
    }

    /**
     * Get all sites from parked paths and linked sites.
     */
    public function parked(): \Illuminate\Support\Collection
    {
        $parkedPaths = $this->config->parkedPaths();
        $sites = collect();

        foreach ($parkedPaths as $path) {
            if (! $this->files->isDir($path)) {
                continue;
            }

            $dirContents = $this->files->scandir($path);

            foreach ($dirContents as $dir) {
                if (str_starts_with($dir, '.')) {
                    continue;
                }

                $fullPath = $path . '/' . $dir;

                if ($this->files->isDir($fullPath)) {
                    $sites->put($dir, $fullPath);
                }
            }
        }

        return $sites;
    }

    /**
     * Get all sites (parked + linked).
     */
    public function allSites(): \Illuminate\Support\Collection
    {
        return $this->parked()->merge($this->links());
    }

    /**
     * Secure the given host with TLS.
     */
    public function secure(string $url, ?string $siteConf = null): void
    {
        $tld = $this->config->read()['tld'];
        $fullUrl = $url . '.' . $tld;

        // Generate certificate
        $this->createCertificate($fullUrl);

        // Build the secure server configuration
        $siteConf = $siteConf ?: $this->buildSecureServer($url);

        // Install the configuration
        $this->webServer()->installSite($url, $siteConf);

        $this->webServer()->restart();

        info("The [{$url}] site has been secured with a fresh TLS certificate.");
    }

    /**
     * Unsecure the given URL so it will use HTTP.
     */
    public function unsecure(string $url): void
    {
        $tld = $this->config->read()['tld'];
        $fullUrl = $url . '.' . $tld;

        // Remove certificate
        $this->removeCertificate($fullUrl);

        // Remove web server config (it will fall back to catch-all)
        $this->removeWebServerConfig($url);

        $this->webServer()->restart();

        info("The [{$url}] site will now serve traffic over HTTP.");
    }

    /**
     * Get all secured sites.
     */
    public function secured(): \Illuminate\Support\Collection
    {
        $certsPath = $this->certificatesPath();

        if (! $this->files->isDir($certsPath)) {
            return collect();
        }

        return collect($this->files->scandir($certsPath))
            ->filter(function ($file) {
                return str_ends_with($file, '.crt');
            })->map(function ($file) {
                return str_replace('.crt', '', $file);
            })->values();
    }

    /**
     * Create a self-signed TLS certificate for the given URL.
     */
    public function createCertificate(string $url): void
    {
        $certsPath = $this->certificatesPath();

        $this->files->ensureDirExists($certsPath, user());

        $keyPath = $certsPath . '/' . $url . '.key';
        $csrPath = $certsPath . '/' . $url . '.csr';
        $crtPath = $certsPath . '/' . $url . '.crt';
        $confPath = $certsPath . '/' . $url . '.conf';

        // Build OpenSSL config
        $opensslConf = $this->files->get(__DIR__ . '/../stubs/openssl.conf');
        $opensslConf = str_replace('BERKAN_CERTIFICATE', $url, $opensslConf);
        $this->files->put($confPath, $opensslConf);

        // Generate private key
        $this->cli->run("openssl genrsa -out \"{$keyPath}\" 2048 2>/dev/null");

        // Generate CSR
        $this->cli->run(
            "openssl req -new -key \"{$keyPath}\" -out \"{$csrPath}\" -config \"{$confPath}\" 2>/dev/null"
        );

        // Generate self-signed certificate
        $this->cli->run(
            "openssl x509 -req -days 365 -in \"{$csrPath}\" -signkey \"{$keyPath}\" " .
            "-out \"{$crtPath}\" -extensions v3_req -extfile \"{$confPath}\" 2>/dev/null"
        );

        // Trust the certificate in macOS Keychain
        $this->trustCertificate($crtPath);

        // Cleanup CSR and conf
        $this->files->unlink($csrPath);
    }

    /**
     * Trust the given certificate file in the macOS Keychain.
     */
    public function trustCertificate(string $crtPath): void
    {
        $this->cli->run(
            "sudo security add-trusted-cert -d -r trustRoot " .
            "-k /Library/Keychains/System.keychain \"{$crtPath}\""
        );
    }

    /**
     * Remove the TLS certificate for the given URL.
     */
    public function removeCertificate(string $url): void
    {
        $certsPath = $this->certificatesPath();

        $this->files->unlink($certsPath . '/' . $url . '.key');
        $this->files->unlink($certsPath . '/' . $url . '.crt');
        $this->files->unlink($certsPath . '/' . $url . '.conf');

        // Remove from Keychain
        $this->cli->run(
            "sudo security delete-certificate -c \"{$url}\" /Library/Keychains/System.keychain 2>/dev/null"
        );
    }

    /**
     * Get the stub prefix based on web server type.
     */
    protected function stubPrefix(): string
    {
        return $this->getWebServerType() === 'nginx' ? 'nginx-' : '';
    }

    /**
     * Build a secure server configuration.
     */
    public function buildSecureServer(string $url): string
    {
        $config = $this->config->read();
        $tld = $config['tld'];
        $fullUrl = $url . '.' . $tld;
        $certsPath = $this->certificatesPath();
        $sitePath = $this->getSitePath($url);

        $phpSocket = 'berkan.sock';
        $isolatedVersion = $this->phpVersion($url);
        if ($isolatedVersion) {
            $phpSocket = basename(resolve(PhpFpm::class)->isolatedSocketPath($isolatedVersion));
        }

        $stub = $this->files->get(__DIR__ . '/../stubs/' . $this->stubPrefix() . 'secure.berkan.conf');

        $httpsPort = $config['https_port'] ?? '443';

        return str_replace(
            [
                'BERKAN_HTTPS_SUFFIX',
                'BERKAN_LOOPBACK',
                'BERKAN_HTTP_PORT',
                'BERKAN_HTTPS_PORT',
                'BERKAN_SITE_PATH',
                'BERKAN_SERVER_PATH',
                'BERKAN_HOME_PATH',
                'BERKAN_PHP_SOCKET',
                'BERKAN_SITE',
                'BERKAN_TLD',
                'BERKAN_CERT',
                'BERKAN_KEY',
            ],
            [
                $httpsPort !== '443' ? ':' . $httpsPort : '',
                $config['loopback'] ?? BERKAN_LOOPBACK,
                $config['http_port'] ?? '80',
                $httpsPort,
                $sitePath,
                realpath(__DIR__ . '/../../'),
                $this->config->homePath(),
                $phpSocket,
                $url,
                $tld,
                $certsPath . '/' . $fullUrl . '.crt',
                $certsPath . '/' . $fullUrl . '.key',
            ],
            $stub
        );
    }

    /**
     * Build a standard server configuration.
     */
    public function buildServer(string $url): string
    {
        $config = $this->config->read();
        $tld = $config['tld'];
        $sitePath = $this->getSitePath($url);

        $phpSocket = 'berkan.sock';
        $isolatedVersion = $this->phpVersion($url);
        if ($isolatedVersion) {
            $phpSocket = basename(resolve(PhpFpm::class)->isolatedSocketPath($isolatedVersion));
        }

        $stub = $this->files->get(__DIR__ . '/../stubs/' . $this->stubPrefix() . 'site.berkan.conf');

        return str_replace(
            [
                'BERKAN_LOOPBACK',
                'BERKAN_HTTP_PORT',
                'BERKAN_HTTPS_PORT',
                'BERKAN_SITE_PATH',
                'BERKAN_SERVER_PATH',
                'BERKAN_HOME_PATH',
                'BERKAN_PHP_SOCKET',
                'BERKAN_SITE',
                'BERKAN_TLD',
            ],
            [
                $config['loopback'] ?? BERKAN_LOOPBACK,
                $config['http_port'] ?? '80',
                $config['https_port'] ?? '443',
                $sitePath,
                realpath(__DIR__ . '/../../'),
                $this->config->homePath(),
                $phpSocket,
                $url,
                $tld,
            ],
            $stub
        );
    }

    /**
     * Build a proxy server configuration.
     */
    public function buildProxyServer(string $url, string $host): string
    {
        $config = $this->config->read();
        $tld = $config['tld'];

        $stub = $this->files->get(__DIR__ . '/../stubs/' . $this->stubPrefix() . 'proxy.berkan.conf');

        // Extract host without protocol for WebSocket
        $wsHost = preg_replace('#^https?://#', '', rtrim($host, '/'));

        return str_replace(
            [
                'BERKAN_LOOPBACK',
                'BERKAN_HTTP_PORT',
                'BERKAN_HOME_PATH',
                'BERKAN_PROXY_HOST_WS',
                'BERKAN_PROXY_HOST',
                'BERKAN_SITE',
                'BERKAN_TLD',
            ],
            [
                $config['loopback'] ?? BERKAN_LOOPBACK,
                $config['http_port'] ?? '80',
                $this->config->homePath(),
                $wsHost,
                $host,
                $url,
                $tld,
            ],
            $stub
        );
    }

    /**
     * Build a secure proxy server configuration.
     */
    public function buildSecureProxyServer(string $url, string $host): string
    {
        $config = $this->config->read();
        $tld = $config['tld'];
        $fullUrl = $url . '.' . $tld;
        $certsPath = $this->certificatesPath();

        $stub = $this->files->get(__DIR__ . '/../stubs/' . $this->stubPrefix() . 'secure.proxy.berkan.conf');

        $wsHost = preg_replace('#^https?://#', '', rtrim($host, '/'));
        $httpsPort = $config['https_port'] ?? '443';

        return str_replace(
            [
                'BERKAN_HTTPS_SUFFIX',
                'BERKAN_LOOPBACK',
                'BERKAN_HTTP_PORT',
                'BERKAN_HTTPS_PORT',
                'BERKAN_HOME_PATH',
                'BERKAN_PROXY_HOST_WS',
                'BERKAN_PROXY_HOST',
                'BERKAN_CERT',
                'BERKAN_KEY',
                'BERKAN_SITE',
                'BERKAN_TLD',
            ],
            [
                $httpsPort !== '443' ? ':' . $httpsPort : '',
                $config['loopback'] ?? BERKAN_LOOPBACK,
                $config['http_port'] ?? '80',
                $httpsPort,
                $this->config->homePath(),
                $wsHost,
                $host,
                $certsPath . '/' . $fullUrl . '.crt',
                $certsPath . '/' . $fullUrl . '.key',
                $url,
                $tld,
            ],
            $stub
        );
    }

    /**
     * Proxy the given URL to the given host.
     */
    public function proxyCreate(string $url, string $host, bool $secure = false): void
    {
        $tld = $this->config->read()['tld'];

        if ($secure) {
            $fullUrl = $url . '.' . $tld;
            $this->createCertificate($fullUrl);
            $siteConf = $this->buildSecureProxyServer($url, $host);
        } else {
            $siteConf = $this->buildProxyServer($url, $host);
        }

        // Write proxy metadata
        $this->files->putAsUser(
            $this->sitesPath() . '/' . $url . '.proxy',
            $host
        );

        $this->webServer()->installSite($url, $siteConf);
        $this->webServer()->restart();

        info("Proxy for [{$url}] has been created to [{$host}].");
    }

    /**
     * Remove the given proxy.
     */
    public function proxyDelete(string $url): void
    {
        $this->files->unlink($this->sitesPath() . '/' . $url . '.proxy');
        $this->removeWebServerConfig($url);

        $tld = $this->config->read()['tld'];
        $fullUrl = $url . '.' . $tld;
        $this->removeCertificate($fullUrl);

        $this->webServer()->restart();

        info("Proxy for [{$url}] has been removed.");
    }

    /**
     * Get all proxies.
     */
    public function proxies(): \Illuminate\Support\Collection
    {
        $sitesPath = $this->sitesPath();

        if (! $this->files->isDir($sitesPath)) {
            return collect();
        }

        return collect($this->files->scandir($sitesPath))
            ->filter(function ($file) {
                return str_ends_with($file, '.proxy');
            })->mapWithKeys(function ($file) use ($sitesPath) {
                $name = str_replace('.proxy', '', $file);
                $host = trim($this->files->get($sitesPath . '/' . $file));

                return [$name => $host];
            });
    }

    /**
     * Remove the web server configuration for the given site.
     */
    protected function removeWebServerConfig(string $url): void
    {
        $this->webServer()->removeSite($url);
    }

    /**
     * Get the site path for the given URL.
     */
    public function getSitePath(string $url): string
    {
        // Check linked sites first
        $linkPath = $this->sitesPath() . '/' . $url;

        if ($this->files->exists($linkPath)) {
            if ($this->files->isLink($linkPath)) {
                $target = $this->files->readLink($linkPath);
                return $target !== false ? $target : $linkPath;
            }

            return $linkPath;
        }

        // Check parked paths
        foreach ($this->config->parkedPaths() as $path) {
            $sitePath = $path . '/' . $url;

            if ($this->files->isDir($sitePath)) {
                return $sitePath;
            }
        }

        return '';
    }

    /**
     * Rebuild all site VirtualHost configs without touching certificates.
     *
     * Useful when ports or other config values change (e.g. during install).
     */
    public function rebuildSiteConfigs(): void
    {
        $tld = $this->config->read()['tld'] ?? 'test';
        $secured = $this->secured();
        $rebuilt = [];

        // Rebuild secured site configs
        foreach ($secured as $fullUrl) {
            $siteName = str_replace('.' . $tld, '', $fullUrl);

            $siteConf = $this->buildSecureServer($siteName);
            $this->webServer()->installSite($siteName, $siteConf);
            $rebuilt[$siteName] = true;
        }

        // Rebuild isolated (non-secured) site configs
        foreach ($this->isolated() as $siteName => $phpVersion) {
            if (isset($rebuilt[$siteName])) {
                continue; // Already rebuilt as secured
            }

            // Only rebuild if the site actually exists (linked or parked)
            if ($this->getSitePath($siteName) !== '') {
                $siteConf = $this->buildServer($siteName);
                $this->webServer()->installSite($siteName, $siteConf);
                $rebuilt[$siteName] = true;
            }
        }

        // Rebuild proxy site configs
        foreach ($this->proxies() as $name => $host) {
            $fullUrl = $name . '.' . $tld;
            $certsPath = $this->certificatesPath();

            if ($this->files->exists($certsPath . '/' . $fullUrl . '.crt')) {
                $siteConf = $this->buildSecureProxyServer($name, $host);
            } else {
                $siteConf = $this->buildProxyServer($name, $host);
            }

            $this->webServer()->installSite($name, $siteConf);
        }
    }

    /**
     * Resecure all currently secured sites with a new TLD.
     */
    public function resecureForNewTld(string $oldTld, string $newTld): void
    {
        if (! $this->files->isDir($this->certificatesPath())) {
            return;
        }

        $secured = collect($this->files->scandir($this->certificatesPath()))
            ->filter(function ($file) use ($oldTld) {
                return str_ends_with($file, '.' . $oldTld . '.crt');
            })->map(function ($file) use ($oldTld) {
                return str_replace(['.' . $oldTld . '.crt'], '', $file);
            });

        // Remove old certificates and configs using the OLD TLD directly
        // (unsecure() reads TLD from config which is already updated to newTld)
        $certsPath = $this->certificatesPath();
        foreach ($secured as $siteName) {
            $oldFullUrl = $siteName . '.' . $oldTld;

            $this->removeCertificate($oldFullUrl);
            $this->removeWebServerConfig($siteName);
        }

        // Now secure all sites with the NEW TLD (config already has newTld)
        foreach ($secured as $siteName) {
            $this->secure($siteName);
        }
    }

    /**
     * Get the isolated PHP version for the given site.
     */
    public function phpVersion(string $site): ?string
    {
        $config = $this->config->read();

        return $config['isolated_versions'][$site] ?? null;
    }

    /**
     * Isolate a site to use a specific PHP version.
     */
    public function isolate(string $site, string $phpVersion, bool $silent = false): void
    {
        $config = $this->config->read();

        if (! isset($config['isolated_versions'])) {
            $config['isolated_versions'] = [];
        }

        $config['isolated_versions'][$site] = $phpVersion;
        $this->config->write($config);

        if (! $silent) {
            $displayVersion = str_replace(['php@', 'php'], '', $phpVersion) ?: 'latest';
            info("Site [{$site}] is now using PHP {$displayVersion}.");
        }
    }

    /**
     * Remove PHP version isolation from a site.
     */
    public function removeIsolation(string $site): void
    {
        $config = $this->config->read();

        if (isset($config['isolated_versions'][$site])) {
            unset($config['isolated_versions'][$site]);
            $this->config->write($config);
        }

        info("Site [{$site}] isolation has been removed.");
    }

    /**
     * Get all isolated sites.
     */
    public function isolated(): array
    {
        $config = $this->config->read();

        return $config['isolated_versions'] ?? [];
    }
}
