<?php

namespace Berkan\Tests;

use Berkan\Configuration;
use Berkan\Contracts\WebServer;
use Berkan\DnsMasq;
use Berkan\Filesystem;
use Berkan\PhpFpm;
use Berkan\Status;
use PHPUnit\Framework\MockObject\MockObject;

class StatusTest extends TestCase
{
    protected Status $status;
    protected WebServer|MockObject $webServer;
    protected PhpFpm|MockObject $phpFpm;
    protected DnsMasq|MockObject $dnsMasq;
    protected Configuration $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webServer = $this->createMock(WebServer::class);
        $this->phpFpm = $this->createMock(PhpFpm::class);
        $this->dnsMasq = $this->createMock(DnsMasq::class);
        $this->config = new Configuration(new Filesystem());

        // Create config directory and write base config
        $basePath = $this->config->path();
        mkdir($basePath, 0755, true);
        $this->config->writeBaseConfiguration();

        $this->status = new Status($this->webServer, $this->phpFpm, $this->dnsMasq, $this->config);
    }

    public function test_check_returns_service_statuses(): void
    {
        $this->webServer->method('serviceName')->willReturn('Apache (httpd)');
        $this->webServer->method('status')->willReturn('Running');
        $this->phpFpm->method('status')->willReturn('Running');
        $this->dnsMasq->method('status')->willReturn('Running');

        $result = $this->status->check();

        $this->assertArrayHasKey('Apache (httpd)', $result);
        $this->assertArrayHasKey('PHP-FPM', $result);
        $this->assertArrayHasKey('DnsMasq', $result);
        $this->assertEquals('Running', $result['Apache (httpd)']);
        $this->assertEquals('Running', $result['PHP-FPM']);
        $this->assertEquals('Running', $result['DnsMasq']);
    }

    public function test_all_running_returns_true_when_all_running(): void
    {
        $this->webServer->method('isRunning')->willReturn(true);
        $this->phpFpm->method('isRunning')->willReturn(true);
        $this->dnsMasq->method('isRunning')->willReturn(true);

        $this->assertTrue($this->status->allRunning());
    }

    public function test_all_running_returns_false_when_one_stopped(): void
    {
        $this->webServer->method('isRunning')->willReturn(true);
        $this->phpFpm->method('isRunning')->willReturn(false);
        $this->dnsMasq->method('isRunning')->willReturn(true);

        $this->assertFalse($this->status->allRunning());
    }

    public function test_all_running_returns_false_when_web_server_stopped(): void
    {
        $this->webServer->method('isRunning')->willReturn(false);
        $this->phpFpm->method('isRunning')->willReturn(true);
        $this->dnsMasq->method('isRunning')->willReturn(true);

        $this->assertFalse($this->status->allRunning());
    }

    public function test_all_running_returns_false_when_dns_stopped(): void
    {
        $this->webServer->method('isRunning')->willReturn(true);
        $this->phpFpm->method('isRunning')->willReturn(true);
        $this->dnsMasq->method('isRunning')->willReturn(false);

        $this->assertFalse($this->status->allRunning());
    }
}
