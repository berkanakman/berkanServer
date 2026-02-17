<?php

namespace Berkan\Tests;

use Berkan\Berkan;
use Berkan\CommandLine;
use Berkan\Configuration;
use Berkan\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;

class BerkanTest extends TestCase
{
    protected Berkan $berkan;
    protected CommandLine|MockObject $cli;
    protected Filesystem|MockObject $files;
    protected Configuration $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cli = $this->createMock(CommandLine::class);
        $this->files = $this->createMock(Filesystem::class);
        $this->config = new Configuration(new Filesystem());

        // Create config directory and write base config
        $basePath = $this->config->path();
        mkdir($basePath, 0755, true);
        $this->config->writeBaseConfiguration();

        $this->berkan = new Berkan($this->cli, $this->files, $this->config);
    }

    public function test_version(): void
    {
        $this->assertEquals('1.0.0', Berkan::version());
    }

    public function test_on_latest_version(): void
    {
        $this->assertTrue($this->berkan->onLatestVersion('1.0.0'));
    }

    public function test_sudoers_files_for_apache(): void
    {
        $files = $this->berkan->sudoersFiles();

        $this->assertContains(BREW_PREFIX . '/bin/brew', $files);
        $this->assertContains('/usr/bin/open', $files);
        $this->assertContains(BREW_PREFIX . '/bin/httpd', $files);
        $this->assertContains(BREW_PREFIX . '/bin/apachectl', $files);
        $this->assertNotContains(BREW_PREFIX . '/bin/nginx', $files);
    }

    public function test_sudoers_files_for_nginx(): void
    {
        $this->config->updateKey('web_server', 'nginx');

        $files = $this->berkan->sudoersFiles();

        $this->assertContains(BREW_PREFIX . '/bin/brew', $files);
        $this->assertContains(BREW_PREFIX . '/bin/nginx', $files);
        $this->assertNotContains(BREW_PREFIX . '/bin/httpd', $files);
        $this->assertNotContains(BREW_PREFIX . '/bin/apachectl', $files);
    }

    public function test_trust_creates_sudoers_file(): void
    {
        $this->files->expects($this->once())
            ->method('ensureDirExists')
            ->with('/etc/sudoers.d');

        $this->files->expects($this->once())
            ->method('put')
            ->with(
                '/etc/sudoers.d/berkan',
                $this->callback(function ($content) {
                    return str_contains($content, 'BERKAN_BREW')
                        && str_contains($content, 'NOPASSWD');
                })
            );

        $this->cli->expects($this->once())
            ->method('run')
            ->with('chmod 0440 /etc/sudoers.d/berkan');

        $this->berkan->trust();
    }

    public function test_remove_sudoers(): void
    {
        $this->files->expects($this->once())
            ->method('unlink')
            ->with('/etc/sudoers.d/berkan');

        $this->berkan->removeSudoers();
    }

    public function test_symlink_to_users_bin(): void
    {
        $this->cli->expects($this->once())
            ->method('quietly')
            ->with('rm -f /usr/local/bin/berkan');

        $this->cli->expects($this->once())
            ->method('runAsUser')
            ->with($this->stringContains('ln -s'));

        $this->berkan->symlinkToUsersBin();
    }

    public function test_unlink_from_users_bin(): void
    {
        $this->cli->expects($this->once())
            ->method('quietly')
            ->with('rm -f /usr/local/bin/berkan');

        $this->berkan->unlinkFromUsersBin();
    }

    public function test_server_path(): void
    {
        $path = Berkan::serverPath();

        $this->assertIsString($path);
        $this->assertStringEndsWith('server.php', $path);
    }

    public function test_install_loopback(): void
    {
        $this->files->expects($this->once())
            ->method('get')
            ->willReturn('VALET_LOOPBACK placeholder');

        $this->files->expects($this->once())
            ->method('put')
            ->with(
                '/Library/LaunchDaemons/com.berkan.loopback.plist',
                $this->stringContains('127.0.0.2')
            );

        $this->cli->expects($this->once())
            ->method('run')
            ->with($this->stringContains('launchctl load'));

        $this->berkan->installLoopback('127.0.0.2');
    }

    public function test_remove_loopback(): void
    {
        $this->cli->expects($this->once())
            ->method('quietly')
            ->with($this->stringContains('launchctl unload'));

        $this->files->expects($this->once())
            ->method('unlink')
            ->with('/Library/LaunchDaemons/com.berkan.loopback.plist');

        $this->berkan->removeLoopback();
    }
}
