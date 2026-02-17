<?php

namespace Berkan\Tests;

use Berkan\CommandLine;
use Berkan\Composer;
use Berkan\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;

class ComposerTest extends TestCase
{
    protected Composer $composer;
    protected CommandLine|MockObject $cli;
    protected Filesystem|MockObject $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cli = $this->createMock(CommandLine::class);
        $this->files = $this->createMock(Filesystem::class);
        $this->composer = new Composer($this->cli, $this->files);
    }

    public function test_installed_returns_true_when_package_present(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn('laravel/installer v4.5.0');

        $this->assertTrue($this->composer->installed('laravel/installer'));
    }

    public function test_installed_returns_false_when_package_absent(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn('');

        $this->assertFalse($this->composer->installed('laravel/installer'));
    }

    public function test_installed_version_returns_version(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn("name     : laravel/installer\nversions : * v4.5.0\n");

        $result = $this->composer->installedVersion('laravel/installer');
        $this->assertEquals('4.5.0', $result);
    }

    public function test_installed_version_returns_null_when_not_found(): void
    {
        $this->cli->method('runAsUser')
            ->willReturn('');

        $result = $this->composer->installedVersion('nonexistent/package');
        $this->assertNull($result);
    }

    public function test_install_or_fail_runs_composer_require(): void
    {
        $this->cli->expects($this->once())
            ->method('runAsUser')
            ->with(
                'composer global require laravel/installer',
                $this->anything()
            );

        $this->composer->installOrFail('laravel/installer');
    }

    public function test_install_or_fail_throws_on_failure(): void
    {
        $this->cli->method('runAsUser')
            ->willReturnCallback(function ($cmd, $onError) {
                if ($onError) {
                    $onError(1, 'Installation failed');
                }
                return '';
            });

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Composer was unable to install');

        $this->composer->installOrFail('nonexistent/package');
    }
}
