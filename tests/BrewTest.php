<?php

namespace Berkan\Tests;

use Berkan\Brew;
use Berkan\CommandLine;
use Berkan\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;

class BrewTest extends TestCase
{
    protected Brew $brew;
    protected CommandLine|MockObject $cli;
    protected Filesystem|MockObject $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cli = $this->createMock(CommandLine::class);
        $this->files = $this->createMock(Filesystem::class);
        $this->brew = new Brew($this->cli, $this->files);
    }

    public function test_installed_returns_true_when_formula_present(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php\nhttpd\ndnsmasq");

        $this->assertTrue($this->brew->installed('php'));
    }

    public function test_installed_returns_false_when_formula_absent(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php\nhttpd\ndnsmasq");

        $this->assertFalse($this->brew->installed('nginx'));
    }

    public function test_has_installed_php(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php\nhttpd\ndnsmasq");

        $this->assertTrue($this->brew->hasInstalledPhp());
    }

    public function test_has_installed_php_returns_false_when_no_php(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("httpd\ndnsmasq");

        $this->assertFalse($this->brew->hasInstalledPhp());
    }

    public function test_has_installed_php_detects_versioned_php(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php@8.3\nhttpd");

        $this->assertTrue($this->brew->hasInstalledPhp());
    }

    public function test_has_installed_php_detects_old_php(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php@7.4\nhttpd");

        $this->assertTrue($this->brew->hasInstalledPhp());
    }

    public function test_get_linked_php_formula_returns_php_when_present(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php\nhttpd");

        $this->assertEquals('php', $this->brew->getLinkedPhpFormula());
    }

    public function test_get_linked_php_formula_returns_versioned_php(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php@8.3\nhttpd");

        $this->assertEquals('php@8.3', $this->brew->getLinkedPhpFormula());
    }

    public function test_get_linked_php_formula_returns_null_when_no_php(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("httpd\ndnsmasq");

        $this->assertNull($this->brew->getLinkedPhpFormula());
    }

    public function test_get_linked_php_formula_prefers_unversioned_php(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php\nphp@8.3\nhttpd");

        $this->assertEquals('php', $this->brew->getLinkedPhpFormula());
    }

    public function test_ensure_installed_skips_if_already_installed(): void
    {
        $this->cli->expects($this->once())
            ->method('runAsUser')
            ->with('brew list --formula 2>/dev/null')
            ->willReturn("php\nhttpd");

        $this->brew->ensureInstalled('php');
    }

    public function test_supported_php_versions(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php\nphp@8.3\nhttpd");

        $result = $this->brew->supportedPhpVersions();

        $this->assertContains('php', $result);
        $this->assertContains('php@8.3', $result);
        $this->assertNotContains('httpd', $result);
    }

    public function test_supported_php_versions_includes_old_versions(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php\nphp@7.4\nphp@5.6\nhttpd");

        $result = $this->brew->supportedPhpVersions();

        $this->assertContains('php@7.4', $result);
        $this->assertContains('php@5.6', $result);
    }

    public function test_is_php_version(): void
    {
        $this->assertTrue($this->brew->isPhpVersion('php'));
        $this->assertTrue($this->brew->isPhpVersion('php@8.4'));
        $this->assertTrue($this->brew->isPhpVersion('php@8.1'));
        $this->assertTrue($this->brew->isPhpVersion('php@7.4'));
        $this->assertTrue($this->brew->isPhpVersion('php@5.6'));
        $this->assertFalse($this->brew->isPhpVersion('httpd'));
        $this->assertFalse($this->brew->isPhpVersion('nginx'));
        $this->assertFalse($this->brew->isPhpVersion('dnsmasq'));
    }

    public function test_requires_tap_for_old_versions(): void
    {
        $this->assertTrue($this->brew->requiresTap('php@8.0'));
        $this->assertTrue($this->brew->requiresTap('php@7.4'));
        $this->assertTrue($this->brew->requiresTap('php@7.3'));
        $this->assertTrue($this->brew->requiresTap('php@7.2'));
        $this->assertTrue($this->brew->requiresTap('php@7.1'));
        $this->assertTrue($this->brew->requiresTap('php@7.0'));
        $this->assertTrue($this->brew->requiresTap('php@5.6'));
    }

    public function test_requires_tap_false_for_modern_versions(): void
    {
        $this->assertFalse($this->brew->requiresTap('php'));
        $this->assertFalse($this->brew->requiresTap('php@8.4'));
        $this->assertFalse($this->brew->requiresTap('php@8.3'));
        $this->assertFalse($this->brew->requiresTap('php@8.2'));
        $this->assertFalse($this->brew->requiresTap('php@8.1'));
    }

    public function test_requires_tap_false_for_non_php(): void
    {
        $this->assertFalse($this->brew->requiresTap('httpd'));
        $this->assertFalse($this->brew->requiresTap('nginx'));
        $this->assertFalse($this->brew->requiresTap('mysql'));
    }

    public function test_supported_php_versions_constant_includes_all_versions(): void
    {
        $versions = Brew::SUPPORTED_PHP_VERSIONS;

        $this->assertContains('php', $versions);
        $this->assertContains('php@8.4', $versions);
        $this->assertContains('php@8.3', $versions);
        $this->assertContains('php@8.2', $versions);
        $this->assertContains('php@8.1', $versions);
        $this->assertContains('php@8.0', $versions);
        $this->assertContains('php@7.4', $versions);
        $this->assertContains('php@7.3', $versions);
        $this->assertContains('php@7.2', $versions);
        $this->assertContains('php@7.1', $versions);
        $this->assertContains('php@7.0', $versions);
        $this->assertContains('php@5.6', $versions);
        $this->assertCount(12, $versions);
    }

    public function test_get_php_executable_path(): void
    {
        $path = $this->brew->getPhpExecutablePath('php@8.3');
        $this->assertEquals(BREW_PREFIX . '/opt/php@8.3/bin/php', $path);
    }

    public function test_get_php_executable_path_for_old_version(): void
    {
        $path = $this->brew->getPhpExecutablePath('php@7.4');
        $this->assertEquals(BREW_PREFIX . '/opt/php@7.4/bin/php', $path);
    }

    public function test_get_brew_prefix(): void
    {
        $prefix = Brew::getBrewPrefix();

        $this->assertNotEmpty($prefix);
        $this->assertIsString($prefix);
    }

    public function test_restart_service(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("httpd");

        $this->cli->expects($this->once())
            ->method('quietly')
            ->with('sudo brew services restart httpd');

        $this->brew->restartService('httpd');
    }

    public function test_stop_service(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("httpd");

        $this->cli->expects($this->once())
            ->method('quietly')
            ->with('sudo brew services stop httpd');

        $this->brew->stopService('httpd');
    }

    public function test_start_service(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("httpd");

        $this->cli->expects($this->once())
            ->method('quietly')
            ->with('sudo brew services start httpd');

        $this->brew->startService('httpd');
    }

    public function test_link(): void
    {
        $this->cli->expects($this->once())
            ->method('runAsUser')
            ->with('brew link php')
            ->willReturn('linked');

        $this->assertEquals('linked', $this->brew->link('php'));
    }

    public function test_link_with_force(): void
    {
        $this->cli->expects($this->once())
            ->method('runAsUser')
            ->with('brew link php --force --overwrite')
            ->willReturn('linked');

        $this->assertEquals('linked', $this->brew->link('php', true));
    }

    public function test_unlink(): void
    {
        $this->cli->expects($this->once())
            ->method('runAsUser')
            ->with('brew unlink php')
            ->willReturn('unlinked');

        $this->assertEquals('unlinked', $this->brew->unlink('php'));
    }

    public function test_tap(): void
    {
        $this->cli->expects($this->exactly(2))
            ->method('runAsUser')
            ->willReturnCallback(function ($cmd) {
                $this->assertStringStartsWith('brew tap ', $cmd);
                return '';
            });

        $this->brew->tap(['mongodb/brew', 'custom/tap']);
    }

    public function test_tap_single(): void
    {
        $this->cli->expects($this->once())
            ->method('runAsUser')
            ->with('brew tap shivammathur/php');

        $this->brew->tap(['shivammathur/php']);
    }

    public function test_is_started_service(): void
    {
        $this->cli->method('run')
            ->willReturn("dnsmasq started\nhttpd running");

        $this->assertTrue($this->brew->isStartedService('httpd'));
    }

    public function test_is_started_service_returns_false(): void
    {
        $this->cli->method('run')
            ->willReturn("dnsmasq started");

        $this->assertFalse($this->brew->isStartedService('httpd'));
    }

    public function test_is_started_service_with_started_keyword(): void
    {
        $this->cli->method('run')
            ->willReturn("httpd started");

        $this->assertTrue($this->brew->isStartedService('httpd'));
    }

    public function test_installed_formulae_returns_array(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("php\nhttpd\ndnsmasq");

        $result = $this->brew->installedFormulae();

        $this->assertIsArray($result);
        $this->assertContains('php', $result);
        $this->assertContains('httpd', $result);
        $this->assertContains('dnsmasq', $result);
    }

    public function test_install_or_fail_taps_shivammathur_for_old_versions(): void
    {
        $tapCalled = false;
        $installCalled = false;

        $this->cli->method('runAsUser')
            ->willReturnCallback(function ($cmd) use (&$tapCalled, &$installCalled) {
                if (str_contains($cmd, 'brew tap shivammathur/php')) {
                    $tapCalled = true;
                }
                if (str_contains($cmd, 'brew install php@7.4')) {
                    $installCalled = true;
                }
                return '';
            });

        $this->brew->installOrFail('php@7.4');

        $this->assertTrue($tapCalled);
        $this->assertTrue($installCalled);
    }
}
