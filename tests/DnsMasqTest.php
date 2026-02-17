<?php

namespace Berkan\Tests;

use Berkan\Brew;
use Berkan\CommandLine;
use Berkan\Configuration;
use Berkan\DnsMasq;
use Berkan\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;

class DnsMasqTest extends TestCase
{
    protected DnsMasq $dnsMasq;
    protected Brew|MockObject $brew;
    protected CommandLine|MockObject $cli;
    protected Filesystem $files;
    protected Configuration $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brew = $this->createMock(Brew::class);
        $this->cli = $this->createMock(CommandLine::class);
        $this->files = new Filesystem();
        $this->config = new Configuration($this->files);

        // Create config directory and write base config
        $basePath = $this->config->path();
        mkdir($basePath, 0755, true);
        $this->config->writeBaseConfiguration();
        $this->files->ensureDirExists($basePath . '/dnsmasq.d');

        $this->dnsMasq = new DnsMasq($this->brew, $this->cli, $this->files, $this->config);
    }

    public function test_is_running(): void
    {
        $this->brew->method('isStartedService')
            ->with('dnsmasq')
            ->willReturn(true);

        $this->assertTrue($this->dnsMasq->isRunning());
    }

    public function test_is_not_running(): void
    {
        $this->brew->method('isStartedService')
            ->with('dnsmasq')
            ->willReturn(false);

        $this->assertFalse($this->dnsMasq->isRunning());
    }

    public function test_status_running(): void
    {
        $this->brew->method('isStartedService')->willReturn(true);

        $this->assertEquals('Running', $this->dnsMasq->status());
    }

    public function test_status_stopped(): void
    {
        $this->brew->method('isStartedService')->willReturn(false);

        $this->assertEquals('Stopped', $this->dnsMasq->status());
    }

    public function test_configure_dnsmasq_creates_tld_config(): void
    {
        $dnsmasqConf = $this->tempDir . '/dnsmasq.conf';
        // We can't easily test the real path, but test the TLD config creation
        $berkanConf = $this->config->homePath() . '/dnsmasq.d/tld-test.conf';

        $this->dnsMasq->configureDnsmasq('test');

        $this->assertTrue(file_exists($berkanConf));
        $contents = file_get_contents($berkanConf);
        $this->assertStringContainsString('address=/.test/', $contents);
        $this->assertStringContainsString(BERKAN_LOOPBACK, $contents);
    }

    public function test_restart(): void
    {
        $this->brew->expects($this->once())
            ->method('restartService')
            ->with('dnsmasq');

        $this->dnsMasq->restart();
    }

    public function test_stop(): void
    {
        $this->brew->expects($this->once())
            ->method('stopService')
            ->with('dnsmasq');

        $this->dnsMasq->stop();
    }
}
