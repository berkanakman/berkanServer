<?php

namespace Berkan;

class Ngrok
{
    public CommandLine $cli;
    public Filesystem $files;

    /**
     * Create a new Ngrok instance.
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

            throw new \RuntimeException('Tunnel not ready.');
        }, 250);

        return $response;
    }

    /**
     * Set the Ngrok auth token.
     */
    public function setToken(string $token): void
    {
        $this->cli->runAsUser('ngrok authtoken ' . escapeshellarg($token));
        info('Ngrok auth token has been set.');
    }

    /**
     * Share the site via Ngrok.
     */
    public function share(string $url, string $tld): void
    {
        $domain = $url . '.' . $tld;

        $this->cli->passthru(
            'ngrok http --host-header=rewrite ' . escapeshellarg($domain . ':80')
        );
    }
}
