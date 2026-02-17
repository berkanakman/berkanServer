<?php

namespace Berkan;

class Cloudflared
{
    public CommandLine $cli;
    public Filesystem $files;

    /**
     * Create a new Cloudflared instance.
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Share the site via Cloudflare Tunnel.
     */
    public function share(string $url, string $tld): void
    {
        $domain = $url . '.' . $tld;

        $this->cli->passthru(
            "cloudflared tunnel --url http://{$domain}:80"
        );
    }

    /**
     * Get the current tunnel URL.
     */
    public function currentTunnelUrl(): ?string
    {
        $logFile = '/tmp/berkan-cloudflared.log';

        if (! file_exists($logFile)) {
            return null;
        }

        $contents = file_get_contents($logFile);

        if (preg_match('/https:\/\/[a-zA-Z0-9\-]+\.trycloudflare\.com/', $contents, $matches)) {
            return $matches[0];
        }

        return null;
    }
}
