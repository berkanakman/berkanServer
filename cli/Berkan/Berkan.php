<?php

namespace Berkan;

class Berkan
{
    public CommandLine $cli;
    public Filesystem $files;
    public Configuration $config;

    const VERSION = '1.0.0';

    /**
     * Create a new Berkan instance.
     */
    public function __construct(CommandLine $cli, Filesystem $files, Configuration $config)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Get the Berkan version.
     */
    public static function version(): string
    {
        return static::VERSION;
    }

    /**
     * Determine if this is the latest version of Berkan.
     */
    public function onLatestVersion(string $currentVersion): bool
    {
        // For now, always return true since this is a local tool
        return true;
    }

    /**
     * Symlink the Berkan binary to the user's local bin.
     */
    public function symlinkToUsersBin(): void
    {
        $this->unlinkFromUsersBin();

        $binPath = BREW_PREFIX . '/bin/berkan';

        $this->cli->runAsUser(
            'ln -s "' . realpath(__DIR__ . '/../../berkan') . '" ' . $binPath
        );
    }

    /**
     * Remove the Berkan symlink from the user's local bin.
     */
    public function unlinkFromUsersBin(): void
    {
        $this->cli->quietly('rm -f ' . BREW_PREFIX . '/bin/berkan');
        // Also remove legacy location
        $this->cli->quietly('rm -f /usr/local/bin/berkan');
    }

    /**
     * Get the paths to trust with Berkan.
     */
    public function sudoersFiles(): array
    {
        $config = $this->config->read();
        $webServer = $config['web_server'] ?? 'apache';

        $files = [
            BREW_PREFIX . '/bin/brew',
            '/usr/bin/open',
        ];

        if ($webServer === 'nginx') {
            $files[] = BREW_PREFIX . '/bin/nginx';
        } else {
            $files[] = BREW_PREFIX . '/bin/httpd';
            $files[] = BREW_PREFIX . '/bin/apachectl';
        }

        return $files;
    }

    /**
     * Trust Berkan with sudo.
     */
    public function trust(): void
    {
        $sudoersEntry = $this->buildSudoersEntry();

        $this->files->ensureDirExists('/etc/sudoers.d');
        $this->files->put('/etc/sudoers.d/berkan', $sudoersEntry . PHP_EOL);
        $this->cli->run('chmod 0440 /etc/sudoers.d/berkan');

        info('Sudoers file has been updated for Berkan.');
    }

    /**
     * Remove the Berkan sudoers entry.
     */
    public function removeSudoers(): void
    {
        $this->files->unlink('/etc/sudoers.d/berkan');
    }

    /**
     * Build the sudoers entry for Berkan.
     */
    protected function buildSudoersEntry(): string
    {
        $user = user();
        $lines = [];

        foreach ($this->sudoersFiles() as $binary) {
            $lines[] = "Cmnd_Alias BERKAN_" . strtoupper(basename($binary)) . " = {$binary}";
        }

        $cmndAliases = array_map(function ($binary) {
            return 'BERKAN_' . strtoupper(basename($binary));
        }, $this->sudoersFiles());

        $lines[] = "{$user} ALL=(root) NOPASSWD: " . implode(', ', $cmndAliases);

        return implode(PHP_EOL, $lines);
    }

    /**
     * Install the loopback address plist.
     */
    public function installLoopback(string $loopback): void
    {
        $plistPath = '/Library/LaunchDaemons/com.berkan.loopback.plist';
        $stub = $this->files->get(__DIR__ . '/../stubs/loopback.plist');

        $this->files->put(
            $plistPath,
            str_replace('BERKAN_LOOPBACK', $loopback, $stub)
        );

        $this->cli->run("sudo launchctl load -w {$plistPath}");
    }

    /**
     * Remove the loopback address plist.
     */
    public function removeLoopback(): void
    {
        $plistPath = '/Library/LaunchDaemons/com.berkan.loopback.plist';

        $this->cli->quietly("sudo launchctl unload {$plistPath}");
        $this->files->unlink($plistPath);
    }

    /**
     * Get the server.php script path.
     */
    public static function serverPath(): string
    {
        return realpath(__DIR__ . '/../../server.php');
    }

    /**
     * Write the Berkan environment constants.
     */
    public static function binPath(): string
    {
        return realpath(__DIR__ . '/../../berkan');
    }
}
