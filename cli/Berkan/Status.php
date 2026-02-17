<?php

namespace Berkan;

use Berkan\Contracts\WebServer;

class Status
{
    public WebServer $webServer;
    public PhpFpm $phpFpm;
    public DnsMasq $dnsMasq;
    public Configuration $config;

    /**
     * Create a new Status instance.
     */
    public function __construct(WebServer $webServer, PhpFpm $phpFpm, DnsMasq $dnsMasq, Configuration $config)
    {
        $this->webServer = $webServer;
        $this->phpFpm = $phpFpm;
        $this->dnsMasq = $dnsMasq;
        $this->config = $config;
    }

    /**
     * Get the status of all services.
     */
    public function check(): array
    {
        $statuses = [
            $this->webServer->serviceName() => $this->webServer->status(),
            'PHP-FPM' => $this->phpFpm->status(),
            'DnsMasq' => $this->dnsMasq->status(),
        ];

        // Add database statuses
        $config = $this->config->read();
        $databases = $config['databases'] ?? [];

        if (! empty($databases)) {
            $database = new Database(
                resolve(Brew::class),
                resolve(CommandLine::class),
                resolve(Filesystem::class),
                $this->config
            );

            foreach ($databases as $db) {
                $dbInfo = Database::SUPPORTED_DATABASES[$db] ?? null;
                if ($dbInfo) {
                    $statuses[$dbInfo['label']] = $database->status($db);
                }
            }
        }

        return $statuses;
    }

    /**
     * Display the status of all services.
     */
    public function display(): void
    {
        $statuses = $this->check();

        $rows = [];
        foreach ($statuses as $service => $status) {
            $rows[] = [$service, $status];
        }

        table(['Service', 'Status'], $rows);
    }

    /**
     * Determine if all services are running.
     */
    public function allRunning(): bool
    {
        return $this->webServer->isRunning()
            && $this->phpFpm->isRunning()
            && $this->dnsMasq->isRunning();
    }
}
