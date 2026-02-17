<?php

namespace Berkan\Contracts;

interface WebServer
{
    public function install(): void;
    public function restart(): void;
    public function stop(): void;
    public function start(): void;
    public function uninstall(): void;
    public function isRunning(): bool;
    public function status(): string;
    public function installSite(string $url, string $siteConf): void;
    public function removeSite(string $url): void;
    public function configuredSites(): array;
    public function installConfiguration(): void;
    public function installServer(): void;
    public function errorLogPath(): string;
    public function serviceName(): string;
    public function brewServiceName(): string;
    public function configTestCommand(): string;
    public function confPath(): string;
    public function rewriteSecureFiles(): void;
}
