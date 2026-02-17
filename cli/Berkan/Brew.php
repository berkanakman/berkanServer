<?php

namespace Berkan;

class Brew
{
    public CommandLine $cli;
    public Filesystem $files;

    const SUPPORTED_PHP_VERSIONS = [
        'php',
        'php@8.4',
        'php@8.3',
        'php@8.2',
        'php@8.1',
    ];

    /**
     * Create a new Brew instance.
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Determine if the given formula is installed.
     */
    public function installed(string $formula): bool
    {
        return in_array($formula, $this->installedFormulae());
    }

    /**
     * Get the list of installed formulae.
     */
    public function installedFormulae(): array
    {
        return explode("\n", trim($this->cli->runAsUser('brew list --formula 2>/dev/null')));
    }

    /**
     * Determine if a compatible PHP version is Homebrew installed.
     */
    public function hasInstalledPhp(): bool
    {
        $installed = $this->installedFormulae();

        foreach (static::SUPPORTED_PHP_VERSIONS as $phpVersion) {
            if (in_array($phpVersion, $installed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the aliased formula name for the latest PHP version.
     */
    public function getLinkedPhpFormula(): ?string
    {
        $formulae = $this->installedFormulae();

        if (in_array('php', $formulae)) {
            return 'php';
        }

        foreach (static::SUPPORTED_PHP_VERSIONS as $phpVersion) {
            if (in_array($phpVersion, $formulae)) {
                return $phpVersion;
            }
        }

        return null;
    }

    /**
     * Ensure that the given formula is installed.
     */
    public function ensureInstalled(string $formula, array $options = [], array $taps = []): void
    {
        if ($this->installed($formula)) {
            return;
        }

        $this->installOrFail($formula, $options, $taps);
    }

    /**
     * Install the given formula and throw an exception on failure.
     */
    public function installOrFail(string $formula, array $options = [], array $taps = []): void
    {
        info("Installing {$formula}...");

        if (count($taps) > 0) {
            $this->tap($taps);
        }

        $this->cli->runAsUser(
            trim('brew install ' . $formula . ' ' . implode(' ', $options)),
            function ($exitCode, $errorOutput) use ($formula) {
                warning("Failed to install {$formula}.");
                throw new \DomainException('Brew was unable to install [' . $formula . '].');
            }
        );
    }

    /**
     * Tap the given Homebrew repositories.
     */
    public function tap(array $formulas): void
    {
        foreach ($formulas as $formula) {
            $this->cli->runAsUser('brew tap ' . $formula);
        }
    }

    /**
     * Restart the given Homebrew services.
     */
    public function restartService(string|array $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            if ($this->installed($service)) {
                info("Restarting {$service}...");
                $this->cli->quietly('sudo brew services restart ' . $service);
            }
        }
    }

    /**
     * Stop the given Homebrew services.
     */
    public function stopService(string|array $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            if ($this->installed($service)) {
                info("Stopping {$service}...");
                $this->cli->quietly('sudo brew services stop ' . $service);
            }
        }
    }

    /**
     * Start the given Homebrew services.
     */
    public function startService(string|array $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            if ($this->installed($service)) {
                info("Starting {$service}...");
                $this->cli->quietly('sudo brew services start ' . $service);
            }
        }
    }

    /**
     * Determine if the given service is running.
     */
    public function isStartedService(string $service): bool
    {
        $result = $this->cli->run('brew services list 2>/dev/null');

        return str_contains($result, $service) && (
            str_contains($result, 'started') || str_contains($result, 'running')
        );
    }

    /**
     * Link the given formula.
     */
    public function link(string $formula, bool $force = false): string
    {
        return $this->cli->runAsUser(
            'brew link ' . $formula . ($force ? ' --force --overwrite' : '')
        );
    }

    /**
     * Unlink the given formula.
     */
    public function unlink(string $formula): string
    {
        return $this->cli->runAsUser('brew unlink ' . $formula);
    }

    /**
     * Determine the currently linked PHP.
     */
    public function linkedPhp(): string
    {
        $resolvedLink = $this->files->readLink(BREW_PREFIX . '/bin/php');

        foreach (static::SUPPORTED_PHP_VERSIONS as $phpVersion) {
            if (str_contains($resolvedLink, '/' . $phpVersion . '/') || str_contains($resolvedLink, '/' . str_replace('@', '/', $phpVersion) . '/')) {
                return $phpVersion;
            }
        }

        throw new \DomainException('Unable to determine linked PHP.');
    }

    /**
     * Get list of supported PHP versions that are installed.
     */
    public function supportedPhpVersions(): array
    {
        $installed = $this->installedFormulae();

        return collect(static::SUPPORTED_PHP_VERSIONS)
            ->filter(function ($version) use ($installed) {
                return in_array($version, $installed);
            })->values()->all();
    }

    /**
     * Get the Homebrew prefix.
     */
    public static function getBrewPrefix(): string
    {
        return (PHP_OS_FAMILY === 'Darwin' && PHP_INT_SIZE === 8 && str_contains(php_uname('m'), 'arm'))
            ? '/opt/homebrew'
            : '/usr/local';
    }

    /**
     * Determine if a formula is a PHP version.
     */
    public function isPhpVersion(string $formula): bool
    {
        return in_array($formula, static::SUPPORTED_PHP_VERSIONS);
    }

    /**
     * Get PHP binary path for the given formula.
     */
    public function getPhpExecutablePath(string $phpFormula): string
    {
        return BREW_PREFIX . '/opt/' . $phpFormula . '/bin/php';
    }

    /**
     * Get the full PHP version string for the given formula.
     */
    public function getPhpVersion(string $phpFormula): string
    {
        $result = $this->cli->run(
            $this->getPhpExecutablePath($phpFormula) . ' -r "echo PHP_MAJOR_VERSION.\'.\'.PHP_MINOR_VERSION;"'
        );

        return trim($result);
    }
}
