<?php

namespace Berkan;

class Expose
{
    public CommandLine $cli;
    public Filesystem $files;

    /**
     * Create a new Expose instance.
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Get the current tunnel URL.
     */
    public function currentTunnelUrl(): ?string
    {
        $response = retry(20, function () {
            $body = $this->cli->run('curl -s http://127.0.0.1:4040/api/tunnels 2>/dev/null');

            $body = json_decode($body, true);

            if (isset($body['tunnels'][0]['public_url'])) {
                return $body['tunnels'][0]['public_url'];
            }

            throw new \RuntimeException('Expose tunnel not ready.');
        }, 250);

        return $response;
    }

    /**
     * Share the site via Expose.
     */
    public function share(string $url, string $tld): void
    {
        $domain = $url . '.' . $tld;

        $this->cli->passthru(
            "expose share {$domain}:80"
        );
    }
}
