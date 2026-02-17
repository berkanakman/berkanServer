<?php

namespace Berkan\Tests;

use Berkan\Brew;
use Berkan\CommandLine;
use Berkan\Configuration;
use Berkan\Filesystem;
use Berkan\PhpFpm;
use PHPUnit\Framework\MockObject\MockObject;

class PhpFpmTest extends TestCase
{
    protected PhpFpm $phpFpm;
    protected Brew|MockObject $brew;
    protected CommandLine|MockObject $cli;
    protected Configuration $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brew = $this->createMock(Brew::class);
        $this->cli = $this->createMock(CommandLine::class);
        $this->config = new Configuration(new Filesystem());

        // Create config directory and write base config
        $basePath = $this->config->path();
        mkdir($basePath, 0755, true);
        $this->config->writeBaseConfiguration();

        $this->phpFpm = new PhpFpm($this->brew, $this->cli, new Filesystem(), $this->config);
    }

    public function test_socket_path(): void
    {
        $expected = $this->config->homePath() . '/berkan.sock';
        $this->assertEquals($expected, $this->phpFpm->socketPath());
    }

    public function test_isolated_socket_path_with_versioned_php(): void
    {
        $expected = $this->config->homePath() . '/berkan-8.3.sock';
        $this->assertEquals($expected, $this->phpFpm->isolatedSocketPath('php@8.3'));
    }

    public function test_isolated_socket_path_with_old_php(): void
    {
        $expected = $this->config->homePath() . '/berkan-7.4.sock';
        $this->assertEquals($expected, $this->phpFpm->isolatedSocketPath('php@7.4'));
    }

    public function test_isolated_socket_path_with_php56(): void
    {
        $expected = $this->config->homePath() . '/berkan-5.6.sock';
        $this->assertEquals($expected, $this->phpFpm->isolatedSocketPath('php@5.6'));
    }

    public function test_isolated_socket_path_with_plain_php(): void
    {
        $this->brew->method('getPhpVersion')->willReturn('8.4');

        $expected = $this->config->homePath() . '/berkan-8.4.sock';
        $this->assertEquals($expected, $this->phpFpm->isolatedSocketPath('php'));
    }

    public function test_status_running(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn('php');
        $this->brew->method('isStartedService')->with('php')->willReturn(true);

        $this->assertEquals('Running', $this->phpFpm->status());
    }

    public function test_status_stopped(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn('php');
        $this->brew->method('isStartedService')->with('php')->willReturn(false);

        $this->assertEquals('Stopped', $this->phpFpm->status());
    }

    public function test_is_running(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn('php');
        $this->brew->method('isStartedService')->with('php')->willReturn(true);

        $this->assertTrue($this->phpFpm->isRunning());
    }

    public function test_is_running_returns_false_when_no_php(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn(null);

        $this->assertFalse($this->phpFpm->isRunning());
    }

    public function test_fpm_config_path(): void
    {
        $path = $this->phpFpm->fpmConfigPath('php@8.3');

        $this->assertEquals(BREW_PREFIX . '/etc/php/8.3/php-fpm.d/berkan-fpm.conf', $path);
    }

    public function test_fpm_config_path_for_plain_php(): void
    {
        $this->brew->method('getPhpVersion')->willReturn('8.4');

        $path = $this->phpFpm->fpmConfigPath('php');

        $this->assertEquals(BREW_PREFIX . '/etc/php/8.4/php-fpm.d/berkan-fpm.conf', $path);
    }

    public function test_fpm_config_path_for_old_php(): void
    {
        $path = $this->phpFpm->fpmConfigPath('php@7.4');

        $this->assertEquals(BREW_PREFIX . '/etc/php/7.4/php-fpm.d/berkan-fpm.conf', $path);
    }

    public function test_fpm_config_path_for_php56(): void
    {
        $path = $this->phpFpm->fpmConfigPath('php@5.6');

        $this->assertEquals(BREW_PREFIX . '/etc/php/5.6/php-fpm.d/berkan-fpm.conf', $path);
    }

    public function test_current_version(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn('php@8.3');

        $this->assertEquals('php@8.3', $this->phpFpm->currentVersion());
    }

    public function test_current_version_returns_null_when_no_php(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn(null);

        $this->assertNull($this->phpFpm->currentVersion());
    }

    public function test_restart_calls_brew(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn('php');
        $this->brew->expects($this->once())
            ->method('restartService')
            ->with('php');

        $this->phpFpm->restart();
    }

    public function test_restart_does_nothing_when_no_php(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn(null);
        $this->brew->expects($this->never())
            ->method('restartService');

        $this->phpFpm->restart();
    }

    public function test_start_calls_brew(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn('php@8.3');
        $this->brew->expects($this->once())
            ->method('startService')
            ->with('php@8.3');

        $this->phpFpm->start();
    }

    public function test_start_does_nothing_when_no_php(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn(null);
        $this->brew->expects($this->never())
            ->method('startService');

        $this->phpFpm->start();
    }

    public function test_stop_stops_all_installed_php(): void
    {
        $this->brew->method('installed')
            ->willReturnCallback(function ($formula) {
                return in_array($formula, ['php', 'php@8.3']);
            });

        $this->brew->expects($this->exactly(2))
            ->method('stopService');

        $this->phpFpm->stop();
    }

    public function test_supported_php_formulae_constant(): void
    {
        $this->assertContains('php', PhpFpm::SUPPORTED_PHP_FORMULAE);
        $this->assertContains('php@8.4', PhpFpm::SUPPORTED_PHP_FORMULAE);
        $this->assertContains('php@8.3', PhpFpm::SUPPORTED_PHP_FORMULAE);
        $this->assertContains('php@8.2', PhpFpm::SUPPORTED_PHP_FORMULAE);
        $this->assertContains('php@8.1', PhpFpm::SUPPORTED_PHP_FORMULAE);
        $this->assertContains('php@8.0', PhpFpm::SUPPORTED_PHP_FORMULAE);
        $this->assertContains('php@7.4', PhpFpm::SUPPORTED_PHP_FORMULAE);
        $this->assertContains('php@5.6', PhpFpm::SUPPORTED_PHP_FORMULAE);
        $this->assertCount(12, PhpFpm::SUPPORTED_PHP_FORMULAE);
    }

    public function test_isolate_version_installs_php_if_not_present(): void
    {
        $this->brew->method('installed')->willReturn(false);

        $this->brew->expects($this->once())
            ->method('installOrFail')
            ->with('php@7.4');

        $this->brew->expects($this->once())
            ->method('restartService')
            ->with('php@7.4');

        $this->phpFpm->isolateVersion('php@7.4');
    }

    public function test_isolate_version_skips_install_when_already_present(): void
    {
        $this->brew->method('installed')->willReturn(true);

        $this->brew->expects($this->never())
            ->method('installOrFail');

        $this->brew->expects($this->once())
            ->method('restartService')
            ->with('php@8.3');

        $this->phpFpm->isolateVersion('php@8.3');
    }

    public function test_remove_isolation(): void
    {
        // Create a fake config path
        $fpmDir = BREW_PREFIX . '/etc/php/8.3/php-fpm.d';
        // Since we can't create files at BREW_PREFIX in tests,
        // just verify the method doesn't throw when file doesn't exist
        $this->phpFpm->removeIsolation('php@8.3');
        $this->assertTrue(true);
    }

    public function test_install_configuration_with_no_php_does_nothing(): void
    {
        $this->brew->method('getLinkedPhpFormula')->willReturn(null);

        // Should not throw, just warn
        $this->phpFpm->installConfiguration();
        $this->assertTrue(true);
    }
}
