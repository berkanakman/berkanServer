<?php

namespace Berkan;

class Composer
{
    public CommandLine $cli;
    public Filesystem $files;

    /**
     * Create a new Composer instance.
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Determine if the given package is installed.
     */
    public function installed(string $package): bool
    {
        $result = $this->cli->runAsUser('composer global show ' . $package . ' 2>/dev/null');

        return str_contains($result, $package);
    }

    /**
     * Install or fail the given package.
     */
    public function installOrFail(string $package): void
    {
        info("Installing {$package}...");

        $this->cli->runAsUser('composer global require ' . $package, function ($exitCode, $errorOutput) use ($package) {
            warning("Could not install {$package}.");
            throw new \DomainException('Composer was unable to install [' . $package . '].');
        });
    }

    /**
     * Get the installed version of the given package.
     */
    public function installedVersion(string $package): ?string
    {
        $result = $this->cli->runAsUser('composer global show ' . $package . ' 2>/dev/null');

        if (preg_match('/versions?\s*:\s*\*?\s*v?([\d.]+)/i', $result, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
