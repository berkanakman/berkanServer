<?php

namespace Berkan\Tests;

use Berkan\Berkan;
use Berkan\Brew;
use Berkan\CommandLine;
use Berkan\Configuration;
use Berkan\Contracts\WebServer;
use Berkan\Diagnose;
use Berkan\DnsMasq;
use Berkan\Filesystem;
use Berkan\PhpFpm;
use PHPUnit\Framework\MockObject\MockObject;

class DiagnoseTest extends TestCase
{
    protected Diagnose $diagnose;
    protected CommandLine|MockObject $cli;
    protected Filesystem|MockObject $files;
    protected Configuration $config;
    protected Brew|MockObject $brew;
    protected WebServer|MockObject $webServer;
    protected PhpFpm|MockObject $phpFpm;
    protected DnsMasq|MockObject $dnsMasq;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cli = $this->createMock(CommandLine::class);
        $this->files = $this->createMock(Filesystem::class);
        $this->config = new Configuration(new Filesystem());
        $this->brew = $this->createMock(Brew::class);
        $this->webServer = $this->createMock(WebServer::class);
        $this->phpFpm = $this->createMock(PhpFpm::class);
        $this->dnsMasq = $this->createMock(DnsMasq::class);

        // Create config directory and write base config
        $basePath = $this->config->path();
        mkdir($basePath, 0755, true);
        $this->config->writeBaseConfiguration();

        $this->diagnose = new Diagnose(
            $this->cli,
            $this->files,
            $this->config,
            $this->brew,
            $this->webServer,
            $this->phpFpm,
            $this->dnsMasq
        );
    }

    public function test_run_returns_diagnostics_array(): void
    {
        $this->webServer->method('serviceName')->willReturn('Apache (httpd)');
        $this->webServer->method('status')->willReturn('Running');
        $this->webServer->method('confPath')->willReturn('/etc/httpd/httpd.conf');
        $this->webServer->method('configTestCommand')->willReturn('apachectl configtest 2>&1');

        $this->phpFpm->method('status')->willReturn('Running');
        $this->phpFpm->method('socketPath')->willReturn('/tmp/berkan.sock');
        $this->dnsMasq->method('status')->willReturn('Running');
        $this->brew->method('supportedPhpVersions')->willReturn(['php', 'php@8.3']);
        $this->brew->method('linkedPhp')->willReturn('php');

        $this->files->method('exists')->willReturn(true);

        $this->cli->method('run')->willReturn('Syntax OK');

        $result = $this->diagnose->run();

        $this->assertArrayHasKey('Berkan Version', $result);
        $this->assertEquals(Berkan::version(), $result['Berkan Version']);
        $this->assertArrayHasKey('PHP Version', $result);
        $this->assertArrayHasKey('Operating System', $result);
        $this->assertArrayHasKey('Homebrew Prefix', $result);
        $this->assertArrayHasKey('TLD', $result);
        $this->assertArrayHasKey('Web Server', $result);
        $this->assertArrayHasKey('Apache (httpd) Status', $result);
        $this->assertArrayHasKey('PHP-FPM Status', $result);
        $this->assertArrayHasKey('DnsMasq Status', $result);
        $this->assertArrayHasKey('Installed PHP', $result);
        $this->assertArrayHasKey('Linked PHP', $result);
        $this->assertArrayHasKey('Parked Paths', $result);
    }

    public function test_run_handles_linked_php_exception(): void
    {
        $this->webServer->method('serviceName')->willReturn('Nginx');
        $this->webServer->method('status')->willReturn('Stopped');
        $this->webServer->method('confPath')->willReturn('/etc/nginx/nginx.conf');
        $this->webServer->method('configTestCommand')->willReturn('nginx -t 2>&1');

        $this->phpFpm->method('status')->willReturn('Stopped');
        $this->phpFpm->method('socketPath')->willReturn('/tmp/berkan.sock');
        $this->dnsMasq->method('status')->willReturn('Stopped');
        $this->brew->method('supportedPhpVersions')->willReturn([]);
        $this->brew->method('linkedPhp')->willThrowException(new \DomainException('Not found'));

        $this->files->method('exists')->willReturn(false);
        $this->cli->method('run')->willReturn('');

        $result = $this->diagnose->run();

        $this->assertEquals('Not found', $result['Linked PHP']);
    }

    public function test_run_shows_correct_tld(): void
    {
        $this->config->updateKey('tld', 'local');

        $this->webServer->method('serviceName')->willReturn('Nginx');
        $this->webServer->method('status')->willReturn('Running');
        $this->webServer->method('confPath')->willReturn('/etc/nginx/nginx.conf');
        $this->webServer->method('configTestCommand')->willReturn('nginx -t 2>&1');
        $this->phpFpm->method('status')->willReturn('Running');
        $this->phpFpm->method('socketPath')->willReturn('/tmp/berkan.sock');
        $this->dnsMasq->method('status')->willReturn('Running');
        $this->brew->method('supportedPhpVersions')->willReturn([]);
        $this->brew->method('linkedPhp')->willReturn('php');
        $this->files->method('exists')->willReturn(false);
        $this->cli->method('run')->willReturn('');

        $result = $this->diagnose->run();

        $this->assertEquals('local', $result['TLD']);
    }

    public function test_run_shows_installed_databases(): void
    {
        $this->config->updateKey('databases', ['mysql', 'redis']);

        $this->webServer->method('serviceName')->willReturn('Apache (httpd)');
        $this->webServer->method('status')->willReturn('Running');
        $this->webServer->method('confPath')->willReturn('/etc/httpd/httpd.conf');
        $this->webServer->method('configTestCommand')->willReturn('apachectl configtest 2>&1');
        $this->phpFpm->method('status')->willReturn('Running');
        $this->phpFpm->method('socketPath')->willReturn('/tmp/berkan.sock');
        $this->dnsMasq->method('status')->willReturn('Running');
        $this->brew->method('supportedPhpVersions')->willReturn([]);
        $this->brew->method('linkedPhp')->willReturn('php');
        $this->files->method('exists')->willReturn(false);
        $this->cli->method('run')->willReturn('');

        $result = $this->diagnose->run();

        $this->assertStringContainsString('mysql', $result['Installed Databases']);
        $this->assertStringContainsString('redis', $result['Installed Databases']);
    }

    public function test_run_shows_no_databases_when_none_installed(): void
    {
        $this->webServer->method('serviceName')->willReturn('Nginx');
        $this->webServer->method('status')->willReturn('Running');
        $this->webServer->method('confPath')->willReturn('/etc/nginx/nginx.conf');
        $this->webServer->method('configTestCommand')->willReturn('nginx -t 2>&1');
        $this->phpFpm->method('status')->willReturn('Running');
        $this->phpFpm->method('socketPath')->willReturn('/tmp/berkan.sock');
        $this->dnsMasq->method('status')->willReturn('Running');
        $this->brew->method('supportedPhpVersions')->willReturn([]);
        $this->brew->method('linkedPhp')->willReturn('php');
        $this->files->method('exists')->willReturn(false);
        $this->cli->method('run')->willReturn('');

        $result = $this->diagnose->run();

        $this->assertEquals('None', $result['Installed Databases']);
    }
}
