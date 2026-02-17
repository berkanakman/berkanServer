<?php

namespace Berkan\Tests;

use Berkan\Apache;
use Berkan\Brew;
use Berkan\CommandLine;
use Berkan\Configuration;
use Berkan\Contracts\WebServer;
use Berkan\Filesystem;
use Berkan\Site;
use PHPUnit\Framework\MockObject\MockObject;

class ApacheTest extends TestCase
{
    protected Apache $apache;
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

        $this->apache = new Apache($this->brew, $this->cli, $this->files, $this->config, $this->site);
    }

    public function test_implements_web_server_interface(): void
    {
        $this->assertInstanceOf(WebServer::class, $this->apache);
    }

    public function test_service_name(): void
    {
        $this->assertEquals('Apache (httpd)', $this->apache->serviceName());
    }

    public function test_brew_service_name(): void
    {
        $this->assertEquals('httpd', $this->apache->brewServiceName());
    }

    public function test_config_test_command(): void
    {
        $this->assertEquals('sudo apachectl configtest 2>&1', $this->apache->configTestCommand());
    }

    public function test_conf_path(): void
    {
        $this->assertEquals(BREW_PREFIX . '/etc/httpd/httpd.conf', $this->apache->confPath());
    }

    public function test_apache_conf_path(): void
    {
        $this->assertEquals(BREW_PREFIX . '/etc/httpd/httpd.conf', $this->apache->apacheConfPath());
    }

    public function test_error_log_path(): void
    {
        $expected = $this->config->homePath() . '/Log/apache-error.log';
        $this->assertEquals($expected, $this->apache->errorLogPath());
    }

    public function test_status_running(): void
    {
        $this->brew->method('isStartedService')
            ->with('httpd')
            ->willReturn(true);

        $this->assertEquals('Running', $this->apache->status());
    }

    public function test_status_stopped(): void
    {
        $this->brew->method('isStartedService')
            ->with('httpd')
            ->willReturn(false);

        $this->assertEquals('Stopped', $this->apache->status());
    }

    public function test_is_running(): void
    {
        $this->brew->method('isStartedService')
            ->with('httpd')
            ->willReturn(true);

        $this->assertTrue($this->apache->isRunning());
    }

    public function test_build_configuration_replaces_basic_placeholders(): void
    {
        $stub = 'Listen VALET_LOOPBACK:80 User VALET_USER Group VALET_GROUP Path VALET_HOME_PATH';

        $result = $this->apache->buildConfiguration($stub);

        $this->assertStringContainsString(BERKAN_LOOPBACK, $result);
        $this->assertStringNotContainsString('VALET_LOOPBACK', $result);
        $this->assertStringNotContainsString('VALET_USER', $result);
        $this->assertStringNotContainsString('VALET_GROUP', $result);
        $this->assertStringNotContainsString('VALET_HOME_PATH', $result);
        $this->assertStringContainsString('staff', $result);
    }

    public function test_build_configuration_replaces_port_placeholders(): void
    {
        $stub = 'Listen VALET_LOOPBACK:VALET_HTTP_PORT HTTPS VALET_HTTPS_PORT';

        $result = $this->apache->buildConfiguration($stub);

        $this->assertStringNotContainsString('VALET_HTTP_PORT', $result);
        $this->assertStringNotContainsString('VALET_HTTPS_PORT', $result);
        $this->assertStringContainsString('80', $result);
        $this->assertStringContainsString('443', $result);
    }

    public function test_build_configuration_uses_custom_ports(): void
    {
        $this->config->updateKey('http_port', '8080');
        $this->config->updateKey('https_port', '8443');

        $stub = 'Listen VALET_LOOPBACK:VALET_HTTP_PORT HTTPS VALET_HTTPS_PORT';

        $result = $this->apache->buildConfiguration($stub);

        $this->assertStringContainsString('8080', $result);
        $this->assertStringContainsString('8443', $result);
    }

    public function test_build_configuration_replaces_server_path(): void
    {
        $stub = 'ServerRoot VALET_SERVER_PATH';

        $result = $this->apache->buildConfiguration($stub);

        $this->assertStringNotContainsString('VALET_SERVER_PATH', $result);
    }

    public function test_build_configuration_replaces_homebrew_path(): void
    {
        $stub = 'LoadModule VALET_HOMEBREW_PATH/lib/modules';

        $result = $this->apache->buildConfiguration($stub);

        $this->assertStringNotContainsString('VALET_HOMEBREW_PATH', $result);
        $this->assertStringContainsString(BREW_PREFIX, $result);
    }

    public function test_install_site_creates_config_file(): void
    {
        $this->apache->installSite('mysite', '<VirtualHost>test</VirtualHost>');

        $confPath = $this->config->homePath() . '/Apache/mysite.conf';
        $this->assertTrue(file_exists($confPath));
        $this->assertEquals('<VirtualHost>test</VirtualHost>', file_get_contents($confPath));
    }

    public function test_remove_site_deletes_config_file(): void
    {
        $apachePath = $this->config->homePath() . '/Apache';
        mkdir($apachePath, 0755, true);
        file_put_contents($apachePath . '/mysite.conf', 'config');

        $this->apache->removeSite('mysite');

        $this->assertFalse(file_exists($apachePath . '/mysite.conf'));
    }

    public function test_remove_site_does_nothing_when_not_exists(): void
    {
        $this->apache->removeSite('nonexistent');
        $this->assertTrue(true);
    }

    public function test_configured_sites(): void
    {
        $apachePath = $this->config->homePath() . '/Apache';
        mkdir($apachePath, 0755, true);

        file_put_contents($apachePath . '/berkan.conf', 'default');
        file_put_contents($apachePath . '/mysite.conf', 'site config');
        file_put_contents($apachePath . '/another.conf', 'another config');

        $sites = $this->apache->configuredSites();

        $this->assertContains('mysite.conf', $sites);
        $this->assertContains('another.conf', $sites);
        $this->assertNotContains('berkan.conf', $sites);
    }

    public function test_configured_sites_returns_empty_when_no_directory(): void
    {
        $this->assertEquals([], $this->apache->configuredSites());
    }

    public function test_restart_checks_config_before_restarting(): void
    {
        $this->cli->method('run')
            ->with('sudo apachectl configtest 2>&1')
            ->willReturn('Syntax OK');

        $this->brew->expects($this->once())
            ->method('restartService')
            ->with('httpd');

        $this->apache->restart();
    }

    public function test_restart_does_not_restart_on_config_failure(): void
    {
        $this->cli->method('run')
            ->with('sudo apachectl configtest 2>&1')
            ->willReturn('AH00526: Syntax error');

        $this->brew->expects($this->never())
            ->method('restartService');

        $this->apache->restart();
    }

    public function test_stop(): void
    {
        $this->brew->expects($this->once())
            ->method('stopService')
            ->with('httpd');

        $this->apache->stop();
    }

    public function test_start(): void
    {
        $this->brew->expects($this->once())
            ->method('startService')
            ->with('httpd');

        $this->apache->start();
    }
}
