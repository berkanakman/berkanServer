<?php

namespace Berkan\Tests;

use Berkan\Brew;
use Berkan\CommandLine;
use Berkan\Configuration;
use Berkan\Contracts\WebServer;
use Berkan\Filesystem;
use Berkan\Nginx;
use Berkan\Site;
use PHPUnit\Framework\MockObject\MockObject;

class NginxTest extends TestCase
{
    protected Nginx $nginx;
    protected Brew|MockObject $brew;
    protected CommandLine|MockObject $cli;
    protected Filesystem $files;
    protected Configuration $config;
    protected Site|MockObject $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brew = $this->createMock(Brew::class);
        $this->cli = $this->createMock(CommandLine::class);
        $this->files = new Filesystem();
        $this->config = new Configuration($this->files);
        $this->site = $this->createMock(Site::class);

        // Create config directory and write base config
        $basePath = $this->config->path();
        mkdir($basePath, 0755, true);
        $this->config->writeBaseConfiguration();

        $this->nginx = new Nginx($this->brew, $this->cli, $this->files, $this->config, $this->site);
    }

    public function test_implements_web_server_interface(): void
    {
        $this->assertInstanceOf(WebServer::class, $this->nginx);
    }

    public function test_service_name(): void
    {
        $this->assertEquals('Nginx', $this->nginx->serviceName());
    }

    public function test_brew_service_name(): void
    {
        $this->assertEquals('nginx', $this->nginx->brewServiceName());
    }

    public function test_config_test_command(): void
    {
        $this->assertEquals('sudo nginx -t 2>&1', $this->nginx->configTestCommand());
    }

    public function test_conf_path(): void
    {
        $this->assertEquals(BREW_PREFIX . '/etc/nginx/nginx.conf', $this->nginx->confPath());
    }

    public function test_error_log_path(): void
    {
        $expected = $this->config->homePath() . '/Log/nginx-error.log';
        $this->assertEquals($expected, $this->nginx->errorLogPath());
    }

    public function test_status_running(): void
    {
        $this->brew->method('isStartedService')
            ->with('nginx')
            ->willReturn(true);

        $this->assertEquals('Running', $this->nginx->status());
    }

    public function test_status_stopped(): void
    {
        $this->brew->method('isStartedService')
            ->with('nginx')
            ->willReturn(false);

        $this->assertEquals('Stopped', $this->nginx->status());
    }

    public function test_is_running(): void
    {
        $this->brew->method('isStartedService')
            ->with('nginx')
            ->willReturn(true);

        $this->assertTrue($this->nginx->isRunning());
    }

    public function test_build_configuration_replaces_basic_placeholders(): void
    {
        $stub = 'worker VALET_LOOPBACK user VALET_USER group VALET_GROUP home VALET_HOME_PATH';

        $result = $this->nginx->buildConfiguration($stub);

        $this->assertStringContainsString(BERKAN_LOOPBACK, $result);
        $this->assertStringNotContainsString('VALET_LOOPBACK', $result);
        $this->assertStringNotContainsString('VALET_USER', $result);
        $this->assertStringNotContainsString('VALET_GROUP', $result);
        $this->assertStringNotContainsString('VALET_HOME_PATH', $result);
    }

    public function test_build_configuration_replaces_port_placeholders(): void
    {
        $stub = 'listen VALET_LOOPBACK:VALET_HTTP_PORT ssl VALET_HTTPS_PORT';

        $result = $this->nginx->buildConfiguration($stub);

        $this->assertStringNotContainsString('VALET_HTTP_PORT', $result);
        $this->assertStringNotContainsString('VALET_HTTPS_PORT', $result);
        $this->assertStringContainsString('80', $result);
        $this->assertStringContainsString('443', $result);
    }

    public function test_build_configuration_uses_custom_ports(): void
    {
        $this->config->updateKey('http_port', '8080');
        $this->config->updateKey('https_port', '8443');

        $stub = 'listen VALET_LOOPBACK:VALET_HTTP_PORT ssl VALET_HTTPS_PORT';

        $result = $this->nginx->buildConfiguration($stub);

        $this->assertStringContainsString('8080', $result);
        $this->assertStringContainsString('8443', $result);
    }

    public function test_build_configuration_replaces_server_path(): void
    {
        $stub = 'root VALET_SERVER_PATH';

        $result = $this->nginx->buildConfiguration($stub);

        $this->assertStringNotContainsString('VALET_SERVER_PATH', $result);
    }

    public function test_build_configuration_replaces_homebrew_path(): void
    {
        $stub = 'include VALET_HOMEBREW_PATH/etc/nginx/mime.types';

        $result = $this->nginx->buildConfiguration($stub);

        $this->assertStringNotContainsString('VALET_HOMEBREW_PATH', $result);
        $this->assertStringContainsString(BREW_PREFIX, $result);
    }

    public function test_install_site_creates_config_file(): void
    {
        $this->nginx->installSite('mysite', 'server { listen 80; }');

        $confPath = $this->config->homePath() . '/Nginx/mysite.conf';
        $this->assertTrue(file_exists($confPath));
        $this->assertEquals('server { listen 80; }', file_get_contents($confPath));
    }

    public function test_remove_site_deletes_config_file(): void
    {
        $nginxPath = $this->config->homePath() . '/Nginx';
        mkdir($nginxPath, 0755, true);
        file_put_contents($nginxPath . '/mysite.conf', 'config');

        $this->nginx->removeSite('mysite');

        $this->assertFalse(file_exists($nginxPath . '/mysite.conf'));
    }

    public function test_remove_site_does_nothing_when_not_exists(): void
    {
        $this->nginx->removeSite('nonexistent');
        $this->assertTrue(true);
    }

    public function test_configured_sites(): void
    {
        $nginxPath = $this->config->homePath() . '/Nginx';
        mkdir($nginxPath, 0755, true);

        file_put_contents($nginxPath . '/berkan.conf', 'default');
        file_put_contents($nginxPath . '/mysite.conf', 'site config');
        file_put_contents($nginxPath . '/another.conf', 'another config');

        $sites = $this->nginx->configuredSites();

        $this->assertContains('mysite.conf', $sites);
        $this->assertContains('another.conf', $sites);
        $this->assertNotContains('berkan.conf', $sites);
    }

    public function test_configured_sites_returns_empty_when_no_directory(): void
    {
        $this->assertEquals([], $this->nginx->configuredSites());
    }

    public function test_restart_with_successful_config(): void
    {
        $this->cli->method('run')
            ->with('sudo nginx -t 2>&1')
            ->willReturn('nginx: configuration file test is successful');

        $this->brew->expects($this->once())
            ->method('restartService')
            ->with('nginx');

        $this->nginx->restart();
    }

    public function test_restart_with_syntax_ok(): void
    {
        $this->cli->method('run')
            ->with('sudo nginx -t 2>&1')
            ->willReturn('nginx: the configuration file syntax is ok');

        $this->brew->expects($this->once())
            ->method('restartService')
            ->with('nginx');

        $this->nginx->restart();
    }

    public function test_restart_does_not_restart_on_config_failure(): void
    {
        $this->cli->method('run')
            ->with('sudo nginx -t 2>&1')
            ->willReturn('nginx: [emerg] unknown directive');

        $this->brew->expects($this->never())
            ->method('restartService');

        $this->nginx->restart();
    }

    public function test_stop(): void
    {
        $this->brew->expects($this->once())
            ->method('stopService')
            ->with('nginx');

        $this->nginx->stop();
    }

    public function test_start(): void
    {
        $this->brew->expects($this->once())
            ->method('startService')
            ->with('nginx');

        $this->nginx->start();
    }
}
